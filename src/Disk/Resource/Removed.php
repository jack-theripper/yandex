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

use Arhitector\Yandex\DiskClient;
use Arhitector\Yandex\Disk\AbstractResource;
use Arhitector\Yandex\Entity;
use Arhitector\Yandex\Entity\TrashResource;
use InvalidArgumentException;

/**
 * It is a removed resource.
 *
 * @package Arhitector\Yandex\Disk\Resource
 */
class Removed extends AbstractResource
{

    /**
     * @var string Identifier or path of the resource on the disk.
     */
    protected $resourcePath;

    /**
     * It is a removed resource.
     *
     * @param string     $path
     * @param DiskClient $client
     */
	public function __construct(string $path, DiskClient $client)
	{
        if ( ! is_scalar($path) || trim($path) == '')
        {
            throw new InvalidArgumentException('The "path" parameter must not be an empty string.');
        }

		$this->resourcePath = (string) $path;
		$this->client = $client;

		$this->setSort('created');
	}

    /**
     * Returns identifier or path of the resource on the disk.
     *
     * @return string
     */
    public function getResourcePath(): string
    {
        return (string) $this->resourcePath;
    }

	/**
	 *	Восстановление файла или папки из Корзины
	 *	В корзине файлы с одинаковыми именами в действительности именют постфикс к имени в виде unixtime
	 *
	 *	@param	mixed	$name	оставляет имя как есть и если boolean это заменяет overwrite
	 *	@param	boolean	$overwrite
	 *	@return	mixed
	 */
	public function restore($name = null, $overwrite = false)
	{
		if (is_bool($name))
		{
			$overwrite = $name;
			$name = null;
		}

		if ($name instanceof Closed)
		{
			$name = $name->getResourcePath();
		}

		if ( ! empty($name) && ! is_string($name))
		{
			throw new \InvalidArgumentException('Новое имя для восстанавливаемого ресурса должо быть строкой');
		}

		$request = new Request($this->uri->withPath($this->uri->getPath().'trash/resources/restore')
			->withQuery(http_build_query([
				'path'      => $this->getResourcePath(),
				'name'      => (string) $name,
				'overwrite' => (bool) $overwrite
			], null, '&')), 'PUT');

		$response = $this->client->sendRequest($request);

		if ($response->getStatusCode() == 201 || $response->getStatusCode() == 202)
		{
			$this->setContents([]);

			if ($response->getStatusCode() == 202)
			{
				$response = json_decode($response->getBody(), true);

				if (isset($response['operation']))
				{
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}
			}

			return $this->client->getResource($name);
		}

		return false;
	}

	/**
	 * Удаление файла или папки
	 *
	 * @return mixed
	 */
	public function delete()
	{
		try
		{
		    $request = $this->client->createRequest('DELETE', $this->client->createUri('/trash/resources')
                ->withQuery(http_build_query(['path' => $this->getResourcePath()])));
			$response = $this->client->sendRequest($request);

			if ($response->getStatusCode() == 202)
			{
				$this->setEntity(null); // clear entity
				$response = json_decode($response->getBody(), true);

				if ( ! empty($response['operation']))
				{
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}

				return true;
			}

			return $response->getStatusCode() == 204;
		}
		catch (\Exception $exc)
		{
			return false;
		}
	}

    /**
     * @inheritDoc
     * @return Entity|TrashResource
     */
    public function getEntity(): TrashResource
    {
        if ( ! $this->entity || $this->isModified())
        {
            $this->entity = new TrashResource($this->getRawContents());
        }

        return $this->entity;
    }

    /**
     * @inheritDoc
     */
    protected function getRawContents(): array
    {
        $request = $this->client->createRequest('GET',
            $this->client->createUri('/trash/resources')
                ->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
                    'path' => $this->getResourcePath()
                ]), null, '&'))
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() == 200)
        {
            $response = json_decode($response->getBody(), true);

            if ( ! empty($response))
            {
                $this->isModified = false;
                $this->entity = new TrashResource($response);
            }
        }

        return $response;
    }

}
