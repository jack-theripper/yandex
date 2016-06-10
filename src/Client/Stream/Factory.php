<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Client\Stream
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Client\Stream;

use Http\Message\StreamFactory;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Stream;

/**
 * Интерфейс для более эффективного управления потоками, нежели то, что предлагает http-php
 *
 * @package Arhitector\Yandex\Client\Stream
 */
class Factory implements StreamFactory
{

	/**
	 * @var StreamInterface
	 */
	//protected $stream;


	/**
	 * Устанавливает Stream для обработки ответа
	 *
	 * @param StreamInterface $stream
	 *
	 * @return $this
	 */
	/*public function useStream(StreamInterface $stream = null)
	{
		$this->stream = $stream;

		return $this;
	}*/

	/**
	 * Конструирует поток
	 *
	 * @param mixed $body
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	/*public function createStream($body = null)
	{
		if ( ! $body instanceof StreamInterface)
		{
			if ( ! $this->getStream())
			{
				$this->stream = new Stream('php://temp', 'rw');
			}

			if (is_resource($body))
			{
				$this->getStream()->__construct($body);
			}
			else if ($body !== null)
			{
				$this->getStream()->write((string) $body);
			}

			$body = $this->getStream();
		}

		$body->rewind();

		return $body;
	}*/

	public function createStream($body = null)
	{
		if (!$body instanceof StreamInterface) {
			if (is_resource($body)) {
				$body = new Stream($body);
			} else {
				$stream = new Stream('php://temp', 'rw');

				if (null !== $body) {
					$stream->write((string) $body);
				}

				$body = $stream;
			}
		}

		$body->rewind();

		return $body;
	}

	/**
	 * Получает объект потока, если такой был установлен.
	 *
	 * @return StreamInterface
	 */
	/*public function getStream()
	{
		return $this->stream;
	}*/

}