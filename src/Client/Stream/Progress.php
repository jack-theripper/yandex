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

use Laminas\Diactoros\Exception\UnseekableStreamException;
use League\Event\EmitterTrait;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Stream;

/**
 * Class Progress stream.
 *
 * @package Arhitector\Yandex\Client\Stream
 */
class Progress extends Stream implements StreamInterface
{
	use EmitterTrait;

	/**
	 * @var int Размер передаваемого тела.
	 */
	protected $totalSize = 0;

	/**
	 * @var int Количество байт прочитанных из потока.
	 */
	protected $readSize = 0;

	/**
	 * Progress constructor.
	 *
	 * @param resource|string $stream
	 * @param string          $mode
	 */
	public function __construct($stream, $mode)
	{
		parent::__construct($stream, $mode);

		$this->totalSize = $this->getSize();
	}

	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *                    them. Fewer than $length bytes may be returned if underlying stream
	 *                    call returns fewer bytes.
	 *
	 * @return string Returns the data read from the stream, or an empty string
	 *     if no bytes are available.
	 * @throws \RuntimeException if an error occurs.
	 */
	public function read($length): string
	{
		$this->readSize += $length;
		$percent = round(100 / $this->totalSize * $this->readSize, 2);
		$this->emit('progress', min(100.0, $percent));

		return parent::read($length);
	}

	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 * @throws \RuntimeException if unable to read or an error occurs while
	 *     reading.
	 */
	public function getContents(): string
	{
		$this->readSize = $this->totalSize;
		$this->emit('progress', 100.0);

		return parent::getContents();
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *                    based on the seek offset. Valid values are identical to the built-in
	 *                    PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *                    offset bytes SEEK_CUR: Set position to current location plus offset
	 *                    SEEK_END: Set position to end-of-stream plus offset.
	 *
	 * @return void
	 * @throws \RuntimeException on failure.
	 */
	public function seek($offset, $whence = SEEK_SET): void
	{
		try {
			parent::seek($offset, $whence); // <-- catch
			$this->readSize = $offset;
		} catch (UnseekableStreamException $exception) {

		}
	}

}
