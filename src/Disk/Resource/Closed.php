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
use Arhitector\Yandex\Client\Stream\Progress;
use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Disk\AbstractResource;
use Arhitector\Yandex\Disk\Exception\AlreadyExistsException;
use League\Event\Event;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;

/**
 * Закрытый ресурс.
 *
 * @property-read   string     $name
 * @property-read   string     $created
 * @property-read   array|null $custom_properties
 * @property-read   string     $modified
 * @property-read   string     $media_type
 * @property-read   string     $path
 * @property-read   string     $md5
 * @property-read   string     $type
 * @property-read   string     $mime_type
 * @property-read   integer    $size
 * @property-read   string     $docviewer
 *
 * @package Arhitector\Yandex\Disk\Resource
 */
class Closed extends AbstractResource
{

	/**
	 * Конструктор.
	 *
	 * @param string|array                   $resource путь к существующему либо новому ресурсу
	 * @param \Arhitector\Yandex\Disk        $parent
	 * @param \Psr\Http\Message\UriInterface $uri
	 */
	public function __construct($resource, Disk $parent, \Psr\Http\Message\UriInterface $uri)
	{
		if (is_array($resource)) {
			if (empty($resource['path'])) {
				throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
			}

			$this->setContents($resource);
			$this->store['docviewer'] = $this->createDocViewerUrl();
			$resource = $resource['path'];
		}

		if (!is_scalar($resource)) {
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $resource;
		$this->client = $parent;
		$this->uri = $uri;
	}

	/**
	 * Получает информацию о ресурсе
	 *
	 * @param array $allowed выбрать ключи
	 *
	 * @return mixed
	 *
	 * @TODO    добавить clearModify(), тем самым сделать возможность получать списки ресурсов во вложенных папках.
	 */
	public function toArray(array $allowed = null)
	{
		if (!$this->_toArray() || $this->isModified()) {
			$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources')
				->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
					'path' => $this->getPath()
				]), '', '&')), 'GET'));

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
	 * Позводляет получить метаинформацию из custom_properties
	 *
	 * @param string $index
	 * @param mixed  $default
	 *
	 * @return mixed|null
	 */
	public function getProperty($index, $default = null)
	{
		$properties = $this->get('custom_properties', []);

		if (isset($properties[$index])) {
			return $properties[$index];
		}

		if ($default instanceof \Closure) {
			return $default($this);
		}

		return $default;
	}

	/**
	 * Получает всю метаинформацию и custom_properties
	 *
	 * @return  array
	 */
	public function getProperties()
	{
		return $this->get('custom_properties', []);
	}

	/**
	 * Добавление метаинформации для ресурса
	 *
	 * @param    mixed $meta  строка либо массив значений
	 * @param    mixed $value NULL чтобы удалить определённую метаинформаию когда $meta строка
	 *
	 * @return Closed
	 * @throws \LengthException
	 */
	public function set($meta, $value = null)
	{
		if (!is_array($meta)) {
			if (!is_scalar($meta)) {
				throw new \InvalidArgumentException('Индекс метаинформации должен быть простого типа.');
			}

			$meta = [(string) $meta => $value];
		}

		if (empty($meta)) {
			throw new \OutOfBoundsException('Не было передано ни одного значения для добавления метаинформации.');
		}

		/*if (mb_strlen(json_encode($meta, JSON_UNESCAPED_UNICODE), 'UTF-8') > 1024)
		{
			throw new \LengthException('Максимальный допустимый размер объекта метаинформации составляет 1024 байт.');
		}*/

		$request = (new Request($this->uri->withPath($this->uri->getPath() . 'resources')
			->withQuery(http_build_query(['path' => $this->getPath()], '', '&')), 'PATCH'));

		$request->getBody()
			->write(json_encode(['custom_properties' => $meta]));

		$response = $this->client->send($request);

		if ($response->getStatusCode() == 200) {
			$this->setContents(json_decode($response->getBody(), true));
			$this->store['docviewer'] = $this->createDocViewerUrl();
		}

		return $this;
	}

	/**
	 * Разрешает обновление свойств объекта как массива
	 *
	 * @param    string $key
	 * @param    mixed  $value
	 *
	 * @return  void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Магический метод set. Добавляет метаинформацию
	 *
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Разрешает использование unset() к метаинформации
	 *
	 * @param   string $key
	 *
	 * @return  void
	 * @throws  \RuntimeException
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($key)
	{
		$this->set($key, null);
	}

	/**
	 * Магический метод unset. Удаляет метаинформацию.
	 *
	 * @return void
	 */
	public function __unset($key)
	{
		$this->set($key, null);
	}

	/**
	 * Удаление файла или папки
	 *
	 * @param   boolean $permanently TRUE Признак безвозвратного удаления
	 *
	 * @return  bool|\Arhitector\Yandex\Disk\Operation|\Arhitector\Yandex\Disk\Resource\Removed
	 */
	public function delete($permanently = false)
	{
		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources')
			->withQuery(http_build_query([
				'path'        => $this->getPath(),
				'permanently' => (bool) $permanently
			])), 'DELETE'));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 204) {
			$this->setContents([]);

			$this->emit('delete', $this, $this->client);
			$this->client->emit('delete', $this, $this->client);

			if ($response->getStatusCode() == 202) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['operation'])) {
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}
			}

			try {
				/*$resource = $this->client->getTrashResource('/', 0);
				$resource = $this->client->getTrashResources(1, $resource->get('total', 0) - 1)->getFirst();

				if ($resource->has() && $resource->get('origin_path') == $this->getPath())
				{
					return $resource;
				}*/
			} catch (\Exception $exc) {
			}

			return true;
		}

		return false;
	}

	/**
	 * Перемещение файла или папки.
	 * Перемещать файлы и папки на Диске можно, указывая текущий путь к ресурсу и его новое положение.
	 * Если запрос был обработан без ошибок, API составляет тело ответа в зависимости от вида указанного ресурса –
	 * ответ для пустой папки или файла отличается от ответа для непустой папки. (Если запрос вызвал ошибку,
	 * возвращается подходящий код ответа, а тело ответа содержит описание ошибки).
	 * Приложения должны самостоятельно следить за статусами запрошенных операций.
	 *
	 * @param   string|\Arhitector\Yandex\Disk\Resource\Closed $destination новый путь.
	 * @param   boolean                                        $overwrite   признак перезаписи файлов. Учитывается,
	 *                                                                      если ресурс перемещается в папку, в которой
	 *                                                                      уже есть ресурс с таким именем.
	 *
	 * @return bool|\Arhitector\Yandex\Disk\Operation
	 */
	public function move($destination, $overwrite = false)
	{
		if ($destination instanceof Closed) {
			$destination = $destination->getPath();
		}

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/move')
			->withQuery(http_build_query([
				'from'      => $this->getPath(),
				'path'      => $destination,
				'overwrite' => (bool) $overwrite
			], '', '&')), 'POST'));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 201) {
			$this->path = $destination;
			$response = json_decode($response->getBody(), true);

			if (isset($response['operation'])) {
				$response['operation'] = $this->client->getOperation($response['operation']);
				$this->emit('operation', $response['operation'], $this, $this->client);
				$this->client->emit('operation', $response['operation'], $this, $this->client);

				return $response['operation'];
			}

			return true;
		}

		return false;
	}

	/**
	 *    Создание папки, если ресурса с таким же именем нет
	 *
	 * @return    \Arhitector\Yandex\Disk\Resource\Closed
	 * @throws    mixed
	 */
	public function create()
	{
		try {
			$this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources')
				->withQuery(http_build_query([
					'path' => $this->getPath()
				], '', '&')), 'PUT'));
			$this->setContents([]);
		} catch (\Exception $exc) {
			throw $exc;
		}

		return $this;
	}

	/**
	 * Публикация ресурса\Закрытие доступа
	 *
	 * @param   boolean $publish TRUE открыть доступ, FALSE закрыть доступ
	 *
	 * @return  \Arhitector\Yandex\Disk\Resource\Closed|\Arhitector\Yandex\Disk\Resource\Opened
	 */
	public function setPublish($publish = true)
	{
		$request = 'resources/unpublish';

		if ($publish) {
			$request = 'resources/publish';
		}

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . $request)
			->withQuery(http_build_query([
				'path' => $this->getPath()
			], '', '&')), 'PUT'));

		if ($response->getStatusCode() == 200) {
			$this->setContents([]);

			if ($publish && $this->has('public_key')) {
				return $this->client->getPublishResource($this->get('public_key'));
			}
		}

		return $this;
	}

	/**
	 * Скачивает файл
	 *
	 * @param resource|StreamInterface|string $destination Путь, по которому будет сохранён файл
	 *                                                     StreamInterface будет записан в поток
	 *                                                     resource открытый на запись
	 * @param mixed                           $overwrite
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws AlreadyExistsException
	 * @throws \OutOfBoundsException
	 * @throws \UnexpectedValueException
	 */
	public function download($destination, $overwrite = false)
	{
		$destination_type = gettype($destination);

		if (!$this->has()) {
			throw new NotFoundException('Не удалось найти запрошенный ресурс.');
		}

		if (is_resource($destination)) {
			$destination = new Stream($destination);
		}

		if ($destination instanceof StreamInterface) {
			if (!$destination->isWritable()) {
				throw new \OutOfBoundsException('Дескриптор файла должен быть открыт с правами на запись.');
			}
		} else {
			if ($destination_type == 'string') {
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
		}

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/download')
			->withQuery(http_build_query(['path' => $this->getPath()], '', '&')), 'GET'));

		if ($response->getStatusCode() == 200) {
			$response = json_decode($response->getBody(), true);

			if (isset($response['href'])) {
				$response = $this->client->send(new Request($response['href'], 'GET'));

				if ($response->getStatusCode() == 200) {
					$stream = $response->getBody();

					while (!$stream->eof()) {
						$destination->write($stream->read(16384));
					}

					$stream->close();
					$this->emit('downloaded', $this, $destination, $this->client);
					$this->client->emit('downloaded', $this, $destination, $this->client);

					if ($destination_type == 'object') {
						return $destination;
					}

					return $destination->getSize();
				}

				return false;
			}
		}

		throw new \UnexpectedValueException('Не удалось запросить разрешение на скачивание, повторите заново.');
	}

	/**
	 * Копирование файла или папки
	 *
	 * @param   string|Closed $destination
	 * @param   bool          $overwrite
	 *
	 * @return bool
	 */
	public function copy($destination, $overwrite = false)
	{
		if ($destination instanceof Closed) {
			$destination = $destination->getPath();
		}

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/copy')
			->withQuery(http_build_query([
				'from'      => $this->getPath(),
				'path'      => $destination,
				'overwrite' => (bool) $overwrite
			], '', '&')), 'POST'));

		if ($response->getStatusCode() == 201) {
			$response = json_decode($response->getBody(), true);

			if (isset($response['operation'])) {
				$response['operation'] = $this->client->getOperation($response['operation']);
				$this->emit('operation', $response['operation'], $this, $this->client);
				$this->client->emit('operation', $response['operation'], $this, $this->client);

				return $response['operation'];
			}

			return true;
		}

		return false;
	}

	/**
	 * Загрузить файл на диск
	 *
	 * @param   mixed  $file_path         может быть как путь к локальному файлу, так и URL к файлу.
	 * @param   bool   $overwrite         если ресурс существует на Яндекс.Диске TRUE перезапишет ресурс.
	 * @param   bool   $disable_redirects помогает запретить редиректы по адресу, TRUE запретит пере адресацию.
	 *
	 * @return  bool|\Arhitector\Yandex\Disk\Operation
	 *
	 * @throws    mixed
	 *
	 * @TODO    Добавить, если передана папка - сжать папку в архив и загрузить.
	 */
	public function upload($file_path, $overwrite = false, $disable_redirects = false)
	{
		if (is_string($file_path)) {
			$scheme = substr($file_path, 0, 7);

			if ($scheme == 'http://' or $scheme == 'https:/') {
				try {
					$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/upload')
						->withQuery(http_build_query([
							'url'  => $file_path,
							'path' => $this->getPath(),
							'disable_redirects' => (int) $disable_redirects
						], '', '&')), 'POST'));
				} catch (AlreadyExistsException $exc) {
					// параметр $overwrite не работает т.к. диск не поддерживает {AlreadyExistsException:409}->rename->delete
					throw new AlreadyExistsException(
						$exc->getMessage() . ' Перезапись для удалённой загрузки не доступна.',
						$exc->getCode(),
						$exc
					);
				}

				$response = json_decode($response->getBody(), true);

				if (isset($response['operation'])) {
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}

				return false;
			}

			$file_path = realpath($file_path);

			if (!is_file($file_path)) {
				throw new \OutOfBoundsException('Локальный файл по такому пути: "' . $file_path . '" отсутствует.');
			}
		} else {
			if (!is_resource($file_path)) {
				throw new \InvalidArgumentException('Параметр "путь к файлу" должен быть строкового типа или открытый файловый дескриптор на чтение.');
			}
		}

		$access_upload = json_decode($this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/upload')
			->withQuery(http_build_query([
				'path'      => $this->getPath(),
				'overwrite' => (int) ((bool) $overwrite),
			], '', '&')), 'GET'))
			->getBody(), true);

		if (!isset($access_upload['href'])) {
			// $this->client->setRetries = 1
			throw new \RuntimeException('Не возможно загрузить локальный файл - не получено разрешение.');
		}

		if ($this->getEmitter()->hasListeners('progress')) {
			$stream = new Progress($file_path, 'rb');
			$stream->addListener('progress', function (Event $event, $percent) {
				$this->emit('progress', $percent);
			});
		} else {
			$stream = new Stream($file_path, 'rb');
		}

		$response = $this->client->send((new Request($access_upload['href'], 'PUT', $stream)));
		$this->emit('uploaded', $this, $this->client, $stream, $response);
		$this->client->emit('uploaded', $this, $this->client, $stream, $response);

		return $response->getStatusCode() == 201;
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

		$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'resources/download')
			->withQuery(http_build_query([
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
	 * Получает ссылку для просмотра документа. Достпно владельцу аккаунта.
	 *
	 * @return bool|string
	 */
	protected function createDocViewerUrl()
	{
		if ($this->isFile()) {
			$docviewer = [
				'url'  => $this->get('path'),
				'name' => $this->get('name')
			];

			if (strpos($docviewer['url'], 'disk:/') === 0) {
				$docviewer['url'] = substr($docviewer['url'], 6);
			}

			$docviewer['url'] = "ya-disk:///disk/{$docviewer['url']}";

			return (string) (new Uri('https://docviewer.yandex.ru'))
				->withQuery(http_build_query($docviewer, '', '&'));
		}

		return false;
	}
}
