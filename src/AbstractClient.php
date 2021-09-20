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

namespace Arhitector\Yandex;

use Arhitector\Yandex\Client\Exception\ServiceException;
use Arhitector\Yandex\Client\HttpClient;
use Arhitector\Yandex\Client\Stream\Factory;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\MessageFactory\DiactorosMessageFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Uri;

/**
 * Базовый клиент, реализует способы аунтифиации
 *
 * @package Arhitector\Yandex
 */
abstract class AbstractClient
{
	/**
	 * @const   адрес API
	 */
	const API_BASEPATH = 'https://oauth.yandex.ru/';

	/**
	 * @var \Psr\Http\Message\UriInterface
	 */
	protected $uri;

	/**
	 * @var \HTTP\Client\HttpClient клиент
	 */
	protected $client;

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
		401 => 'Arhitector\Yandex\Client\Exception\UnauthorizedException',

		/**
		 * Доступ запрещён. Возможно, у приложения недостаточно прав для данного действия.
		 */
		403 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Не удалось найти запрошенный ресурс.
		 */
		404 => 'Arhitector\Yandex\Client\Exception\NotFoundException'
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
	 * @param  \Psr\Http\Message\RequestInterface $request
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
	 * Модифицирует и отправляет запрос.
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

		foreach ($defaultHeaders as $defaultHeader => $value) {
			if (!$request->hasHeader($defaultHeader)) {
				$request = $request->withHeader($defaultHeader, $value);
			}
		}

		$response = $this->client->sendRequest($request);
		$response = $this->transformResponseToException($request, $response);

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
	 * Трансформирует ответ в исключения
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request
	 * @param \Psr\Http\Message\ResponseInterface $response
	 *
	 * @return \Psr\Http\Message\ResponseInterface если статус код не является ошибочным, то вернуть объект ответа
	 * @throws \Arhitector\Yandex\Client\Exception\ServiceException
	 */
	protected function transformResponseToException(RequestInterface $request, ResponseInterface $response)
	{
		if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
			throw new \RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
		}

		if ($response->getStatusCode() >= 500 && $response->getStatusCode() < 600) {
			throw new ServiceException($response->getReasonPhrase(), $response->getStatusCode());
		}

		return $response;
	}
}
