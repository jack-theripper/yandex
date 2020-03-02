<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Client;

use Arhitector\Yandex\Client\Exception\ForbiddenException;
use Arhitector\Yandex\Client\Exception\NotFoundException;
use Arhitector\Yandex\Client\Exception\ServiceException;
use Arhitector\Yandex\Client\Exception\UnauthorizedException;
use Arhitector\Yandex\Client\HttpClient;
use Arhitector\Yandex\Client\Stream\Factory;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\MessageFactory\DiactorosMessageFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Uri;

/**
 * Abstraction layer over the HTTP-client.
 *
 * @package Arhitector\Yandex\Client
 */
abstract class AbstractClient
{
    
    /**
     * The base address of API
     */
    const API_BASEPATH = 'https://oauth.yandex.ru/';
    
    /**
     * @var ClientInterface The HTTP-client that is used
     */
    protected $httpClient;
    
    /**
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;
    
    /**
     * @var string  формат обмена данными
     */
    protected $contentType = 'application/json; charset=utf-8';
    
    /**
     * @var    array   соответствие кодов ответа к типу исключения
     */
    protected $exceptions = [
        
        /**
         * Не авторизован.
         */
        401 => UnauthorizedException::class,
        
        /**
         * Доступ запрещён. Возможно, у приложения недостаточно прав для данного действия.
         */
        403 => ForbiddenException::class,
        
        /**
         * Не удалось найти запрошенный ресурс.
         */
        404 => NotFoundException::class
    ];
    
    /**
     * @var    string    для обращения к API требуется маркер доступа
     */
    protected $tokenRequired = true;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->uri = new Uri(static::API_BASEPATH);
        $this->client = new PluginClient(new HttpClient(new DiactorosMessageFactory, new Factory, [
            CURLOPT_SSL_VERIFYPEER => false
        ]), [
            new RedirectPlugin
        ]);
    }
    
    /**
     * Текущий Uri
     *
     * @return \Psr\Http\Message\UriInterface|Uri
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * Провести аунтификацию в соостветствии с типом сервиса
     *
     * @param \Psr\Http\Message\RequestInterface $request
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    abstract protected function authentication(RequestInterface $request);
    
    /**
     * Формат обмена данными
     *
     * @return    string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
    
    /**
     * Send request. The request will be modified in a special way before sending.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(RequestInterface $request)
    {
        $request = $this->authentication($request);
        $defaultHeaders = [
            'Accept'       => $this->getContentType(),
            'Content-Type' => $this->getContentType()
        ];
        
        foreach ($defaultHeaders as $defaultHeader => $value)
        {
            if ( ! $request->hasHeader($defaultHeader))
            {
                $request = $request->withHeader($defaultHeader, $value);
            }
        }
        
        $response = $this->client->sendRequest($request);
        $response = $this->transformResponseToException($response, $request);
        
        return $response;
    }
    
    /**
     * Устаналивает необходимость токена при запросе.
     *
     * @param $tokenRequired
     *
     * @return boolean  возвращает предыдущее состояние
     */
    protected function setAccessTokenRequired($tokenRequired)
    {
        $previous = $this->tokenRequired;
        $this->tokenRequired = (bool) $tokenRequired;
        
        return $previous;
    }
    
    /**
     * Transforms the response into exceptions
     *
     * @param ResponseInterface $response
     * @param RequestInterface  $request
     *
     * @return ResponseInterface Returns the response object if the operation status is successful
     */
    protected function transformResponseToException(ResponseInterface $response, RequestInterface $request)
    {
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500)
        {
            throw new \RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
        }
        
        if ($response->getStatusCode() >= 500 && $response->getStatusCode() < 600)
        {
            throw new ServiceException($response->getReasonPhrase(), $response->getStatusCode());
        }
        
        return $response;
    }
    
}
