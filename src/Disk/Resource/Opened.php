<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Disk\Resource
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */

namespace Arhitector\Yandex\Disk\Resource;

use Arhitector\Yandex\Client\Container;
use Arhitector\Yandex\Client\Exception\NotFoundException;
use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Disk\AbstractResource;
use Arhitector\Yandex\Disk\Exception\AlreadyExistsException;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;

/**
 * Публичный ресурс.
 *
 * @package Arhitector\Yandex\Disk\Resource
 *
 * @property-read   string    $public_key публичный ключ
 * @property-read   string    $name       название файла
 * @property-read   string    $created    дата создания
 * @property-read   string    $public_url публичный URL
 * @property-read   string    $modified   дата изменения
 * @property-read   string    $media_type медиа тип файла
 * @property-read   string    $path       путь к папке
 * @property-read   string    $md5        MD5 хэш сумма файла
 * @property-read   string    $type       это файл или папка
 * @property-read   string    $mime_type
 * @property-read   int|float $size       размер файла в байтах
 */
class Opened extends AbstractResource
{

	/**
	 * @var    string    ресурс
	 */
	protected $publicKey;

	/**
	 * Конструктор.
	 *
	 * @param string|array                   $public_key URL адрес или публичный ключ ресурса.
	 * @param \Arhitector\Yandex\Disk        $parent
	 * @param \Psr\Http\Message\UriInterface $uri
	 */
	public function __construct($public_key, Disk $parent, \Psr\Http\Message\UriInterface $uri)
	{
		if (is_array($public_key)) {
			if (empty($public_key['public_key'])) {
				throw new \InvalidArgumentException('Параметр "public_key" должен быть строкового типа.');
			}

			$this->setContents($public_key);
			$public_key = $public_key['public_key'];
			$this->store['docviewer'] = $this->createDocViewerUrl();
		}

		if (!is_scalar($public_key)) {
			throw new \InvalidArgumentException('Параметр "public_key" должен быть строкового типа.');
		}

		$this->publicKey = (string) $public_key;
		$this->client = $parent;
		$this->uri = $uri;
	}

	/**
	 * Получить публичный ключ
	 *
	 * @return    string|null
	 */
	public function getPublicKey()
	{
		return $this->publicKey;
	}

	/**
	 * Получает информацию о ресурсе
	 *
	 * @return    mixed
	 */
	public function toArray(array $allowed = null)
	{
		if (!$this->_toArray() || $this->isModified()) {
			$response = $this->client->send((new Request($this->uri->withPath($this->uri->getPath() . 'public/resources')
				->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
					'public_key' => $this->getPublicKey()
				]), '', '&')), 'GET')));

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (!empty($response)) {
					$this->isModified = false;

					if (isset($response['_embedded'])) {
						$response = array_merge($response, $response['_embedded']);
					}

					unset($response['_links'], $response['_embedded']);

					if (isset($response['items'])) {
						$response['items'] = new Container\Collection(array_map(function ($item) {
							return new self($item, $this->client, $this->uri);
						}, $response['items']));
					}

					$this->setContents($response);
					$this->store['docviewer'] = $this->createDocViewerUrl();
				}
			}
		}

		return $this->_toArray($allowed);
	}

	/**
	 * Получает прямую ссылку
	 *
	 * @return    string
	 * @throws    mixed
	 *
	 * @TODO    Не работает для файлов вложенных в публичную папку.
	 */
	public function getLink()
	{
		if (!$this->has()) {
			throw new NotFoundException('Не удалось найти запрошенный ресурс.');
		}

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'public/resources/download')
			->withQuery(http_build_query([
				'public_key' => $this->getPublicKey(),
				'path'       => (string) $this->getPath()
			], '', '&')), 'GET'));

		if ($response->getStatusCode() == 200) {
			$response = json_decode($response->getBody(), true);

			if (isset($response['href'])) {
				return $response['href'];
			}
		}

		throw new \UnexpectedValueException('Не удалось запросить разрешение на скачивание, повторите заново');
	}

	/**
	 * Скачивание публичного файла или папки
	 *
	 * @param resource|StreamInterface|string $destination Путь, по которому будет сохранён файл
	 *                                                     StreamInterface будет записан в поток
	 *                                                     resource открытый на запись
	 * @param boolean                         $overwrite   флаг перезаписи
	 * @param boolean                         $check_hash  провести проверку целостности скачанного файла
	 *                                                     на основе хэша MD5
	 *
	 * @return bool
	 */
	public function download($destination, $overwrite = false, $check_hash = false)
	{
		$destination_type = gettype($destination);

		if (is_resource($destination)) {
			$destination = new Stream($destination);
		}

		if ($destination instanceof StreamInterface) {
			if (!$destination->isWritable()) {
				throw new \OutOfBoundsException('Дескриптор файла должен быть открыт с правами на запись.');
			}
		} else if ($destination_type == 'string') {
			if (is_file($destination) && !$overwrite) {
				throw new AlreadyExistsException('По указанному пути "' . $destination . '" уже существует ресурс.');
			}

			if (!is_writable(dirname($destination))) {
				throw new \OutOfBoundsException('Запрещена запись в директорию, в которой должен быть расположен файл.');
			}

			$destination = new Stream($destination, 'w+b');
		} else {
			throw new \InvalidArgumentException('Такой тип параметра $destination не поддерживается.');
		}

		$response = $this->client->send(new Request($this->getLink(), 'GET'));

		if ($response->getStatusCode() == 200) {
			$stream = $response->getBody();

			if ($check_hash) {
				$ctx = hash_init('md5');

				while (!$stream->eof()) {
					$read_data = $stream->read(1048576);
					$destination->write($read_data);

					hash_update($ctx, $read_data);
				}
			} else {
				while (!$stream->eof()) {
					$destination->write($stream->read(16384));
				}
			}

			$stream->close();
			$this->emit('downloaded', $this, $destination, $this->client);
			$this->client->emit('downloaded', $this, $destination, $this->client);

			if ($destination_type == 'object') {
				return $destination;
			} else if ($check_hash && $destination_type == 'string' && $this->isFile()) {
				if (hash_final($ctx, false) !== $this->get('md5', null)) {
					throw new \RangeException('Ресурс скачан, но контрольные суммы различаются.');
				}
			}

			return $destination->getSize();
		}

		return false;
	}

	/**
	 * Этот файл или такой же находится на моём диске
	 * Метод требует Access Token
	 *
	 * @return    boolean
	 */
	public function hasEqual()
	{
		if ($this->has() && ($path = $this->get('name'))) {
			try {
				return $this->client->getResource(((string) $this->get('path')) . '/' . $path)
					->get('md5', false) === $this->get('md5');
			} catch (\Exception $exc) {
			}
		}

		return false;
	}

	/**
	 * Сохранение публичного файла в «Загрузки» или отдельный файл из публичной папки
	 *
	 * @param    string $name Имя, под которым файл следует сохранить в папку «Загрузки»
	 * @param    string $path Путь внутри публичной папки.
	 *
	 * @return    mixed
	 */
	public function save($name = null, $path = null)
	{
		$parameters = [];

		/**
		 * @var mixed   $name Имя, под которым файл следует сохранить в папку «Загрузки»
		 */
		if (is_string($name)) {
			$parameters['name'] = $name;
		} else if ($name instanceof Closed) {
			$parameters['name'] = substr(strrchr($name->getPath(), '/'), 1);
		}

		/**
		 * @var string  $path (необязательный)
		 * Путь внутри публичной папки. Следует указать, если в значении параметра public_key передан
		 * ключ публичной папки, в которой находится нужный файл.
		 * Путь в значении параметра следует кодировать в URL-формате.
		 */
		if (is_string($path)) {
			$parameters['path'] = $path;
		} else if ($this->getPath() !== null) {
			$parameters['path'] = $this->getPath();
		}

		/**
		 * Если к моменту ответа запрос удалось обработать без ошибок, API отвечает кодом 201 Created и возвращает
		 * ссылку на сохраненный файл в теле ответа (в объекте Link).
		 * Если операция сохранения была запущена, но еще не завершилась, Яндекс.Диск отвечает кодом 202 Accepted.
		 */
		$response = $this->client->send((new Request($this->uri->withPath($this->uri->getPath()
			. 'public/resources/save-to-disk')
			->withQuery(http_build_query([
				'public_key' => $this->getPublicKey()
			] + $parameters, '', '&')), 'POST')));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 201) {
			$response = json_decode($response->getBody(), true);

			if (isset($response['operation'])) {
				$response['operation'] = $this->client->getOperation($response['operation']);
				$this->emit('operation', $response['operation'], $this, $this->client);
				$this->client->emit('operation', $response['operation'], $this, $this->client);

				return $response['operation'];
			}

			if (isset($response['href'])) {
				parse_str((new Uri($response['href']))->getQuery(), $path);

				if (isset($path['path'])) {
					return $this->client->getResource($path['path']);
				}
			}
		}

		return false;
	}

	/**
	 * Устанавливает путь внутри публичной папки
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath($path)
	{
		if (!is_scalar($path)) {
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $path;

		return $this;
	}

	/**
	 * Получает ссылку для просмотра документа.
	 *
	 * @return bool|string
	 * @throws \InvalidArgumentException
	 */
	protected function createDocViewerUrl()
	{
		if ($this->isFile()) {
			$docviewer = [
				'name' => $this->get('name'),
				'url'  => sprintf('ya-disk-public://%s', $this->get('public_key'))
			];

			return (string) (new Uri('https://docviewer.yandex.ru/'))
				->withQuery(http_build_query($docviewer, '', '&'));
		}

		return false;
	}
}
