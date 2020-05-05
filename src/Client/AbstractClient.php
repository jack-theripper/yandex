<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arhitector\Yandex\Client;

use Arhitector\Yandex\Client\Plugin\BaseUriPlugin;
use Arhitector\Yandex\Client\Plugin\ResponseErrorPlugin;
use Arhitector\Yandex\Exception;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstraction layer over the HTTP-client.
 *
 * @package Arhitector\Yandex\Client
 */
abstract class AbstractClient implements RequestFactoryInterface, UriFactoryInterface
{

    /**
     * The base address of API. The default path component of the URI.
     */
    const API_BASE_PATH = null;

    /**
     * @var ClientInterface The HTTP-client that is used.
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface Factory for creating new requests.
     */
    private $requestFactory;

    /**
     * @var UriFactoryInterface Factory for creating new uri object.
     */
    private $uriFactory;

    /**
     * You can use your HTTP-client otherwise any of the available ones will be found.
     *
     * @param ClientInterface|null $httpClient The HTTP-client or `NULL`
     */
    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUrlFactory();
        $this->httpClient = new PluginClient($httpClient ?? Psr18ClientDiscovery::find(), [
            new BaseUriPlugin($this->createUri(static::API_BASE_PATH ?: '')), // Ensure the base path for api
            new RedirectPlugin(), // Ensure the redirects if needed
            new ResponseErrorPlugin(), // Transform response to an error if possible
        ]);
    }

    /**
     * Create a new request.
     *
     * @param string              $method The HTTP method associated with the request.
     * @param UriInterface|string $uri    The URI associated with the request. If
     *                                    the value is a string, the factory MUST create a UriInterface
     *                                    instance based on it.
     *
     * @return RequestInterface
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $uri);
    }

    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return UriInterface
     *
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return $this->uriFactory->createUri($uri);
    }

    /**
     * Send request. The request will be modified in a special way before sending.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function sendRequest(RequestInterface $request)
    {
        $defaultHeaders = [
            'Accept'       => 'application/json; charset=utf-8',
            'Content-Type' => 'application/json; charset=utf-8'
        ];

        foreach ($defaultHeaders as $defaultHeader => $value)
        {
            if ( ! $request->hasHeader($defaultHeader))
            {
                $request = $request->withHeader($defaultHeader, $value);
            }
        }

        try
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $response = $this->getHttpClient()->sendRequest($request);
        }
        catch (Exception $exception) // if non client exceptions should be wrapped?
        {
            throw $exception;
        }

        return $response;
    }

    /**
     * Returns the internal HTTP-client that is used and configured. Usually implements the `Http\Client\HttpClient`
     * interface too
     *
     * @return ClientInterface
     */
    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

}
