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
use Laminas\Diactoros\Stream;

/**
 * Интерфейс для более эффективного управления потоками, нежели то, что предлагает http-php
 *
 * @package Arhitector\Yandex\Client\Stream
 */
class Factory implements StreamFactory
{

	/**
	 * Create a new stream instance.
	 *
	 * @param StreamInterface $body
	 *
	 * @return null|\Laminas\Diactoros\Stream
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function createStream($body = null)
	{
		if (!$body instanceof StreamInterface) {
			if (is_resource($body)) {
				$body = new Stream($body);
			} else {
				$stream = new Stream('php://temp', 'rb+');

				if (null !== $body) {
					$stream->write((string) $body);
				}

				$body = $stream;
			}
		}

		$body->rewind();

		return $body;
	}
}
