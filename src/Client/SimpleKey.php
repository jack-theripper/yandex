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
use Psr\Http\Message\RequestInterface;

/**
 * Ключ API
 *
 * @package Arhitector\Yandex\Client
 */
class SimpleKey extends AbstractClient
{

	/**
	 * @var string  ключ API.
	 */
	protected $accessKey = null;


	/**
	 * Конструктор
	 *
	 * @param    string $accessKey ключ API.
	 *
	 * @throws    \InvalidArgumentException
	 */
	public function __construct($accessKey = null)
	{
		parent::__construct();

		if ($accessKey !== null)
		{
			$this->setAccessKey($accessKey);
		}
	}
	
	/**
	 * Устанавливает ключ API.
	 *
	 * @param string $accessKey ключ API.
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setAccessKey($accessKey)
	{
		if ( ! is_string($accessKey))
		{
			throw new \InvalidArgumentException('Ключ доступа должен быть строкового типа.');
		}

		$this->accessKey = $accessKey;

		return $this;
	}

	/**
	 * Получает установленный ключ API.
	 *
	 * @return string
	 */
	public function getAccessKey()
	{
		return (string) $this->accessKey;
	}
	
	/**
	 * Провести аунтификацию в соостветствии с типом сервиса
	 *
	 * @param   RequestInterface $request
	 *
	 * @return RequestInterface
	 */
	protected function authentication(RequestInterface $request)
	{
		$uri = $request->getUri();
		$key = http_build_query(['key' => $this->getAccessKey()], '', '&');

		if (strlen($uri->getQuery()) > 0)
		{
			$key = '&'.$key;
		}

		return $request->withUri($uri->withQuery($uri->getQuery().$key));
	}

}