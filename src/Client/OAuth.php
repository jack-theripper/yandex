<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Client
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */

namespace Arhitector\Yandex\Client;

use Arhitector\Yandex\AbstractClient;
use Arhitector\Yandex\Client\Exception\UnauthorizedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Request;

/**
 * Клиент для Access Token
 *
 * @package Arhitector\Yandex\Client
 */
class OAuth extends AbstractClient
{
	/**
	 * @var string  OAuth-токен
	 */
	protected $token = null;

	/**
	 * @var string  ID приложения
	 */
	private $clientOauth;

	/**
	 * @var string  пароль приложения
	 */
	private $clientOauthSecret = null;

	/**
	 * Конструктор
	 *
	 * @param    string $token OAuth-токен
	 *
	 * @throws    \InvalidArgumentException
	 */
	public function __construct($token = null)
	{
		parent::__construct();

		if ($token !== null) {
			$this->setAccessToken($token);
		}
	}

	/**
	 * Устанавливает ID приложения
	 *
	 * @param string $client_id
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setClientOauth($client_id)
	{
		if (!is_string($client_id)) {
			throw new \InvalidArgumentException('ID приложения https://oauth.yandex.ru должен быть строкового типа.');
		}

		$this->clientOauth = $client_id;

		return $this;
	}

	/**
	 * Возвращает ID приложения
	 *
	 * @return string
	 */
	public function getClientOauth()
	{
		return $this->clientOauth;
	}

	/**
	 * Устанавливает пароль приложения
	 *
	 * @param string $client_secret
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setClientOauthSecret($client_secret)
	{
		if (!is_string($client_secret)) {
			throw new \InvalidArgumentException('Пароль приложения https://oauth.yandex.ru должен быть строкового типа.');
		}

		$this->clientOauthSecret = $client_secret;

		return $this;
	}

	/**
	 * Возвращает пароль приложения
	 *
	 * @return string
	 */
	public function getClientOauthSecret()
	{
		return $this->clientOauthSecret;
	}

	/**
	 * Устанавливает OAuth-токен.
	 *
	 * @param string $token
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setAccessToken($token)
	{
		if (!is_string($token)) {
			throw new \InvalidArgumentException('OAuth-токен должен быть строкового типа.');
		}

		$this->token = $token;

		return $this;
	}

	/**
	 * Получает установленный токен
	 *
	 * @return string
	 */
	public function getAccessToken()
	{
		return (string) $this->token;
	}

	/**
	 * Запрашивает или обновляет токен
	 *
	 * @param string $username  имя пользователя yandex
	 * @param string $password  пароль от аккаунта
	 * @param bool   $onlyToken вернуть только строковый токен
	 *
	 * @return object|string
	 * @throws UnauthorizedException
	 * @throws \Exception
	 * @example
	 *
	 * $client->refreshAccessToken('username', 'password');
	 *
	 * object(stdClass)[28]
	 * public 'token_type' => string 'bearer' (length=6)
	 * public 'access_token' => string 'c7621`6b09032dwf9a6a7ca765eb39b8' (length=32)
	 * public 'expires_in' => int 31536000
	 * public 'uid' => int 241`68329
	 * public 'created_at' => int 1456882032
	 *
	 * @example
	 *
	 * $client->refreshAccessToken('username', 'password', true);
	 *
	 * string 'c7621`6b09032dwf9a6a7ca765eb39b8' (length=32)
	 */
	public function refreshAccessToken($username, $password, $onlyToken = false)
	{
		if (!is_scalar($username) || !is_scalar($password)) {
			throw new \InvalidArgumentException('Параметры "имя пользователя" и "пароль" должны быть простого типа.');
		}

		$previous = $this->setAccessTokenRequired(false);
		$request = new Request(rtrim(self::API_BASEPATH, ' /') . '/token', 'POST');
		$request->getBody()
			->write(http_build_query([
				'grant_type'    => 'password',
				'client_id'     => $this->getClientOauth(),
				'client_secret' => $this->getClientOauthSecret(),
				'username'      => (string) $username,
				'password'      => (string) $password
			]));

		try {
			$response = json_decode($this->send($request)->wait()->getBody());

			if ($onlyToken) {
				return (string) $response->access_token;
			}

			$response->created_at = time();

			return $response;
		} catch (\Exception $exc) {
			$response = json_decode($exc->getMessage());

			if (isset($response->error_description)) {
				throw new UnauthorizedException($response->error_description);
			}

			throw $exc;
		} finally {
			$this->setAccessTokenRequired($previous);
		}
	}

	/**
	 * Провести аунтификацию в соостветствии с типом сервиса
	 *
	 * @param  \Psr\Http\Message\RequestInterface $request
	 *
	 * @return \Psr\Http\Message\RequestInterface
	 */
	protected function authentication(RequestInterface $request)
	{
		if ($this->tokenRequired) {
			return $request->withHeader('Authorization', sprintf('OAuth %s', $this->getAccessToken()));
		}

		return $request;
	}

	/**
	 * Трансформирует ответ в исключения.
	 * Ответ API, где использует OAuth, отличается от других сервисов.
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request
	 * @param \Psr\Http\Message\ResponseInterface $response
	 *
	 * @return \Psr\Http\Message\ResponseInterface если статус код не является ошибочным, то вернуть объект ответа
	 */
	protected function transformResponseToException(RequestInterface $request, ResponseInterface $response)
	{
		if (isset($this->exceptions[$response->getStatusCode()])) {
			$exception = $this->exceptions[$response->getStatusCode()];

			if (
				$response->hasHeader('Content-Type')
				&& stripos($response->getHeaderLine('Content-Type'), 'json') !== false
			) {
				$responseBody = json_decode($response->getBody(), true);

				if (!isset($responseBody['message'])) {
					$responseBody['message'] = (string) $response->getBody();
				}

				if (is_array($exception)) {
					if (!isset($responseBody['error'], $exception[$responseBody['error']])) {
						return parent::transformResponseToException($request, $response);
					}

					$exception = $exception[$responseBody['error']];
				}

				throw new $exception($responseBody['message'], $response->getStatusCode());
			}
		}

		return parent::transformResponseToException($request, $response);
	}
}
