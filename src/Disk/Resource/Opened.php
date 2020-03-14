<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arhitector\Yandex\Disk\Resource;

use Arhitector\Yandex\Disk\AbstractResource;
use Arhitector\Yandex\Disk\Exception\AlreadyExistsException;
use Arhitector\Yandex\Disk\Operation;
use Arhitector\Yandex\DiskClient;
use Arhitector\Yandex\Entity;
use Arhitector\Yandex\Entity\PublicResource;
use Arhitector\Yandex\Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * It is a public resource.
 *
 * @package Arhitector\Yandex\Disk\Resource
 * @mixin PublicResource
 */
class Opened extends AbstractResource
{

    /**
     * @var string Published resource key.
     */
    protected $publicKey;

    /**
     * @var string Path to the resource in the public folder.
     */
    protected $publicPath;

    /**
     * It is a public resource.
     *
     * @param mixed $url_or_public_key URL-address or published resource key.
     * @param DiskClient $client
     */
    public function __construct($url_or_public_key, DiskClient $client)
    {
        if ( ! is_scalar($url_or_public_key) || trim($url_or_public_key) == '')
        {
            throw new InvalidArgumentException('The "public_key" parameter must not be an empty string.');
        }

        $this->client = $client;
        $this->publicKey = (string) $url_or_public_key;
    }

    /**
     * Returns the public key.
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Returns path to the resource in the public folder.
     *
     * @return string
     */
    public function getPublicPath(): string
    {
        return $this->publicPath ?? '/';
    }

    /**
     * Getting and returns the current entity. If needed it will be refresh from api.
     *
     * @return Entity|PublicResource
     */
    public function getEntity(): PublicResource
    {
        if ( ! $this->entity || $this->isModified())
        {
            $this->entity = new PublicResource($this->getRawContents());
        }

        return $this->entity;
    }

    /**
     * Returns a direct link to the file.
     *
     * @return string
     * @throws Exception
     */
	public function getLink(): string
	{
	    if ( ! $this->isExists()) // The requested resource could not be found
        {
            return null;
        }

        $parameters = [
            'public_key' => $this->getPublicKey(),
            'path'       => $this->getPublicPath() // should be the default "/" (slash)
        ];

        $request = $this->client->createRequest('GET', $this->client->createUri('/public/resources/download')
            ->withQuery(http_build_query($parameters)));

        try
        {
            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() == 200 && ($data = json_decode($response->getBody(), true)))
            {
                return $data['href'] ?? '';
            }

            return null;
        }
        catch (Exception $exception)
        {
            throw $exception;
        }
	}

    /**
     * Downloads a file or folder as zip. Returns an object with data.
     * The handle is not closed, you have to do it manually if you need to further work with this resource.
     *
     * @param string|resource|StreamInterface $destination Where to save data
     * @param bool $overwrite If `$destination` as filepath
     *
     * @return StreamInterface
     * @throws Exception
     */
	public function download($destination, bool $overwrite = true): StreamInterface
	{
	    $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        if (is_string($destination)) // to filesystem
        {
            if (file_exists($destination) && ! $overwrite)
            {
                throw new AlreadyExistsException('The file or folder already exists: "'.$destination.'"');
            }

            if ( ! is_writable(dirname($destination)))
            {
                throw new OutOfBoundsException('Forbidden to write to the directory where the file should be located');
            }

            $destination = $streamFactory->createStreamFromFile($destination, 'w+b');
        }
        else if (is_resource($destination)) // to file handler
        {
            $destination = $streamFactory->createStreamFromResource($destination);
        }
        else if ($destination instanceof StreamInterface) // It will be the same object. As psr-7 stream
        {
            if ( ! $destination->isWritable())
            {
                throw new OutOfBoundsException('The file descriptor must be opened with write permissions.');
            }
        }
        else
        {
            throw new \InvalidArgumentException('The download path is illegitimate');
        }

        $response = $this->client->sendRequest($this->client->createRequest('GET', $this->getLink()));
        $stream = $response->getBody();

        while ( ! $stream->eof()) // Write the received data
        {
            $destination->write($stream->read(16384));
        }

        $stream->close();
        $this->emit('downloaded', $this, $destination, $this->client);

        return $destination;
	}

    /**
     * Save a public resource to the Download folder. If `$toFolderPath` Folder does not exist an exception is thrown.
     *
     * @param string|Closed $filename     [optional] The name under which the resource will be saved in the folder
     * @param string        $nestedPath   Path to the copied resource in the public folder.
     * @param string        $toFolderPath Path to the folder where the resource will be saved. By default, "Downloads"
     *
     * @return Operation|Closed
     * @throws Exception
     * @throws \Exception
     */
	public function save($filename = null, string $nestedPath = null, string $toFolderPath = null)
	{
        $parameters = [
            'public_key' => $this->getPublicKey()
        ];

        if ($filename instanceof Closed) // The name from private resource
        {
            // @todo
            //$filename = substr(strrchr($filename->getResourcePath(), '/'), 1);
        }

        if ($filename != null && is_string($filename)) // The name as string
        {
            if (trim($filename) == '')
            {
                throw new InvalidArgumentException('The name under which the resource will be saved cannot be empty.');
            }

            $parameters['name'] = $filename;
        }

        if (is_string($nestedPath) && trim($nestedPath) != '') // Path in the public folder
        {
            $parameters['path'] = $nestedPath;
        }

        if (is_string($toFolderPath) && trim($toFolderPath) != '') // Where the resource will be saved
        {
            $parameters['save_path'] = $toFolderPath;
        }

        $request = $this->client->createRequest('POST', $this->client->createUri('/public/resources/save-to-disk')
            ->withQuery(http_build_query($parameters)));

        try
        {
            $response = $this->client->sendRequest($request);

            // 202 The operation is performed asynchronously
            // https://cloud-api.yandex.net/v1/disk/operations/<identifier>
            if ($response->getStatusCode() == 202 || $response->getStatusCode() == 201)
            {
                $response = json_decode($response->getBody(), true);

                if (isset($response['operation']))
                {
                    $response['operation'] = $this->client->getOperation($response['operation']);
                    $this->emit('operation', $response['operation'], $this, $this->client);
                    $this->client->emit('operation', $response['operation'], $this, $this->client);

                    return $response['operation'];
                }

                if (isset($response['href']))
                {
                    parse_str(($this->client->createUri($response['href']))->getQuery(), $result);

                    if (isset($result['path']))
                    {
                        return $this->client->getResource($result['path']);
                    }
                }
            }
        }
        catch (Exception $exception)
        {
            throw $exception;
        }

        throw new \Exception('Unrecognized error'); // @todo
	}

	/**
	 * Returns a link to view the document
	 *
	 * @return UriInterface
	 */
	public function getDocviewer(): UriInterface
	{
	    if ( ! $this->isFile())
        {
            return null;
        }

        $parameters = [
            'url'  => 'ya-disk-public://'.$this->getPublicKey(),
            'name' => $this->getName()
        ];

	    return $this->client->createUri('https://docviewer.yandex.ru/')
            ->withQuery(http_build_query($parameters));
	}

    /**
     * @return array Make a request to the API and return all the received data how it is.
     */
    protected function getRawContents(): array
    {
        $request = $this->client->createRequest('GET',
            $this->client->createUri('/public/resources')
                ->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
                    'public_key' => $this->getPublicKey(),
                    'path' => $this->getPublicPath()
                ]), null, '&'))
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() == 200)
        {
            $response = json_decode($response->getBody(), true);

            if ( ! empty($response))
            {
                $this->entity = new PublicResource($response);
            }
        }

        return $response;
    }

}
