<?php

/**
 * Created by Arhitector.
 * Date: 08.03.2016
 * Time: 14:29
 */

namespace Arhitector\Yandex\Disk\Resource;


use Arhitector\Yandex\Client\Container;
use Arhitector\Yandex\Client\Exception\NotFoundException;
use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Disk\AbstractResource;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

//use Arhitector\Yandex\Disk\Exception\AlreadyExistsException;
//use Arhitector\Yandex\Disk\Stream\GzipDecode;

/**
 * Закрытый ресурс.
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
		if (is_array($resource))
		{
			if (empty($resource['path']))
			{
				throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
			}

			$this->setContents($resource);

			if ($this->isFile())
			{
				$docviewer = [
					'url'  => $this->get('path'),
					'name' => $this->get('name')
				];

				if (strpos($docviewer['url'], 'disk:/') === 0)
				{
					$docviewer['url'] = substr($docviewer['url'], 6);
				}

				//?url=ya-disk-public://PIGz1fuuXxttFlS3EU9XKOIl28e4+H7kYZqddBBSVB0=&name=catalog.zip

				$docviewer['url']  = "ya-disk:///disk/{$docviewer['url']}";
				$this->store['docviewer'] = (string) (new Uri('https://docviewer.yandex.ru'))
					->withQuery(http_build_query($docviewer, null, '&'));
			}

			$resource = $resource['path'];
		}

		if ( ! is_scalar($resource))
		{
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $resource;
		$this->parent = $parent;
		$this->uri = $uri;
	}

	/**
	 * Получает информацию о ресурсе
	 *
	 * @param array $allowed    выбрать ключи
	 *
	 * @return mixed
	 *
	 * @TODO    добавить clearModify(), тем самым сделать возможность получать списки ресурсов во вложенных папках.
	 */
	public function toArray(array $allowed = null)
	{
		if ( ! $this->_toArray() || $this->isModified())
		{
			$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources')
				->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
					'path' => $this->getPath()
				]), null, '&')), 'GET'));

			if ($response->getStatusCode() == 200)
			{
				$response = json_decode($response->getBody(), true);

				if ( ! empty($response))
				{
					$this->isModified = false;

					$response['items'] = [];

					if (isset($response['_embedded']))
					{
						$response = array_merge($response, $response['_embedded']);
					}

					unset($response['_links'], $response['_embedded']);

					$response['items'] = new Container\Collection(array_map(function($item) {
						return new self($item, $this->parent, $this->uri);
					}, $response['items']));

					$this->setContents($response);
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

		if (isset($properties[$index]))
		{
			return $properties[$index];
		}

		if ($default instanceof \Closure)
		{
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
	 * @return $this
	 * @throws \LengthException
	 */
	public function set($meta, $value = null)
	{
		if ( ! is_array($meta))
		{
			if ( ! is_scalar($meta))
			{
				throw new \InvalidArgumentException('Индекс метаинформации должен быть простого типа.');
			}

			$meta = [(string) $meta => $value];
		}

		if (empty($meta))
		{
			throw new \OutOfBoundsException('Не было передано ни одного значения для добавления метаинформации.');
		}

		/*if (mb_strlen(json_encode($meta, JSON_UNESCAPED_UNICODE), 'UTF-8') > 1024)
		{
			throw new \LengthException('Максимальный допустимый размер объекта метаинформации составляет 1024 байт.');
		}*/

		$request = (new Request($this->uri->withPath($this->uri->getPath().'resources')
			->withQuery(http_build_query(['path' => $this->getPath()], null, '&')), 'PATCH'));

		$request->getBody()
		        ->write(json_encode(['custom_properties' => $meta]));

		$response = $this->parent->send($request);

		if ($response->getStatusCode() == 200)
		{
			$this->setContents(json_decode($response->getBody(), true));
		}

		return $this;
	}

	/**
	 * Разрешает обновление свойств объекта как массива
	 *
	 * @param    string $key
	 * @param    mixed  $value
	 */
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
	 * @param    string $key
	 *
	 * @throws    \RuntimeException
	 */
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
	 * @param    boolean $permanently TRUE Признак безвозвратного удаления
	 *
	 * @return    mixed
	 */
	public function delete($permanently = false)
	{
		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources')
		                                                      ->withQuery(http_build_query([
			                                                      'path'        => $this->getPath(),
			                                                      'permanently' => (bool) $permanently
		                                                      ])), 'DELETE'));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 204)
		{
			$this->setContents([]);

			$this->emit('disk.delete', $this);

			if ($response->getStatusCode() == 202)
			{
				$response = json_decode($response->getBody(), true);

				if (isset($response['operation']))
				{
					$this->emit('disk.operation', $this);
					$this->parent->emit('disk.operation', $this);

					return $response['operation'];
				}
			}

			try
			{
				/*$trash = $this->parent->trash(null, 1)
					->setSort('deleted', true)
					->getFirst();

				// любая папка в корзине всегда будет в начале списка
				// $this->parent->trash(null, 0)->get('total')
				// $this->parent->trash(null, 1, total - 1)->getFirst()

				if ($trash && $trash->get('origin_path') == $this->getPath())
				{
					return $trash;
				}*/
			}
			catch (\Exception $exc)
			{

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
	 * @param    string|Closed $destination новый путь.
	 * @param   boolean        $overwrite   признак перезаписи файлов. Учитывается, если ресурс перемещается в папку, в
	 *                                      которой уже есть ресурс с таким именем.
	 *
	 * @return bool
	 */
	public function move($destination, $overwrite = false)
	{
		if ($destination instanceof Closed)
		{
			$destination = $destination->getPath();
		}

		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources/move')
		                                                      ->withQuery(http_build_query([
			                                                      'from'      => $this->getPath(),
			                                                      'path'      => $destination,
			                                                      'overwrite' => (bool) $overwrite
		                                                      ], null, '&')), 'POST'));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 201)
		{
			$this->path = $destination;
			$response = json_decode($response->getBody(), true);

			if (isset($response['operation']))
			{
				$this->emit('disk.operation', $this);
				$this->parent->emit('disk.operation', $this);

				return $response['operation'];
			}

			return true;
		}

		return false;
	}

	/**
	 *	Создание папки, если ресурса с таким же именем нет
	 *
	 *	@return	Closed
	 *	@throws	mixed
	 */
	public function create()
	{
		try
		{
			$this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources')
			                                          ->withQuery(http_build_query(['path' => $this->getPath()], null, '&')), 'PUT'));
			$this->setContents([]);
		}
		catch (\Exception $exc)
		{
			throw $exc;
		}

		return $this;
	}

	/**
	 * Публикация ресурса\Закрытие доступа
	 *
	 * @param    string|Resource
	 *
	 * @return Closed|Opened
	 */
	public function publish($publish = true)
	{
		$request = 'resources/unpublish';

		if ($publish)
		{
			$request = 'resources/publish';
		}

		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().$request)
		                                                      ->withQuery(http_build_query(['path' => $this->getPath()], null, '&')), 'PUT'));

		if ($response->getStatusCode() == 200)
		{
			$this->setContents(array());

			if ($publish && $this->has('public_key'))
			{
				return $this->parent->getPublishResource($this->get('public_key'));
			}
		}

		return $this;
	}

	/**
	 * Скачивает файл
	 *
	 * @param string $path Путь, по которому будет сохранён файл
	 * @param mixed  $overwrite
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws AlreadyExistsException
	 * @throws \OutOfBoundsException
	 * @throws \UnexpectedValueException
	 */
	public function download($path, $overwrite = false)
	{
		if ( ! $this->has())
		{
			throw new NotFoundException('Не удалось найти запрошенный ресурс.');
		}

		if (is_file($path) && ! $overwrite)
		{
			throw new AlreadyExistsException('Запрошенный ресурс существует.');
		}

		if ( ! is_writable(dirname($path)))
		{
			throw new \OutOfBoundsException('Запись в директорию где должен быть расположен файл не возможна.');
		}

		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources/download')
		                                                      ->withQuery(http_build_query(['path' => $this->getPath()], null, '&')), 'GET'));

		if ($response->getStatusCode() == 200)
		{
			$response = json_decode($response->getBody(), true);

			if (isset($response['href']))
			{
				$response = $this->parent->send(new Request($response['href'], 'GET'),
					new GzipDecode('php://temp', 'r'));

				if ($response->getStatusCode() == 200)
				{
					$path = fopen($path, 'wb+');

					stream_copy_to_stream($response->getBody()->detach(), $path);

					fclose($path);

					$this->emit('disk.downloaded', $this);
					$this->parent->emit('disk.downloaded', $this);

					return true;
				}

				return false;
			}
		}

		throw new \UnexpectedValueException('Не удалось запросить закачку, повторите заново');
	}

	/**
	 * Копирование файла или папки
	 *
	 * @param    string|Closed
	 *
	 * @return bool
	 */
	public function copy($destination, $overwrite = false)
	{
		if ($destination instanceof Closed)
		{
			$destination = $destination->getPath();
		}

		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'resources/copy')
		                                                      ->withQuery(http_build_query([
			                                                      'from'      => $this->getPath(),
			                                                      'path'      => $destination,
			                                                      'overwrite' => (bool) $overwrite
		                                                      ], null, '&')), 'POST'));

		if ($response->getStatusCode() == 201)
		{
			$response = json_decode($response->getBody(), true);

			//var_dump($response);

			if (isset($response['operation']))
			{
				$this->emit('disk.operation', $this);
				$this->parent->emit('disk.operation', $this);

				return $response['operation'];
			}

			return true;
		}

		return false;
	}

	/**
	 *	Загрузить файл на диск
	 *
	 *	@param	string	$file_path	может быть как путь к локальному файлу, так и URL к файлу
	 *	@param	mixed	$overwrite
	 *
	 *	@return	mixed
	 *
	 *	@throws	mixed
	 */
	public function upload($file_path, $overwrite = false)
	{
		if (is_string($file_path))
		{
			$scheme = substr($file_path, 0, 7);

			if ($scheme == 'http://' or $scheme == 'https:/')
			{
				try
				{
					$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath()
						.'resources/upload')
					                                                      ->withQuery(http_build_query([
						                                                      'url'  => $file_path,
						                                                      'path' => $this->getPath()
					                                                      ], null, '&')), 'POST'));
				}
				catch (AlreadyExistsException $exc)
				{
					// параметр $overwrite не работает т.к. диск не поддерживает {AlreadyExistsException:409}->rename->delete
					throw new AlreadyExistsException($exc->getMessage().' Перезапись для удалённой загрузки не доступна.', $exc->getCode(), $exc);
				}

				$response = json_decode($response->getBody(), true);

				if (isset($response['operation']))
				{
					$this->emit('disk.operation', $this);
					$this->parent->emit('disk.operation', $this);

					return $response['operation'];
				}

				return false;
			}

			$file_path = realpath($file_path);

			if ( ! is_file($file_path))
			{
				throw new \OutOfBoundsException('Локальный файл по такому пути: "'.$file_path.'" отсутствует.');
			}
		}
		else if ( ! is_resource($file_path))
		{
			throw new \InvalidArgumentException('Параметр "путь к файлу" должен быть строкового типа.');
		}
		else
		{
			//stream_copy_to_stream($file_path, fopen('php://temp', 'wb'));
		}

		$access_upload = json_decode($this->parent->send(new Request($this->uri->withPath($this->uri->getPath()
			.'resources/upload')
		                                                                       ->withQuery(http_build_query([
			                                                                       'path'      => $this->getPath(),
			                                                                       'overwrite' => (int) ((boolean) $overwrite),
		                                                                       ], null, '&')), 'GET'))->getBody(), true);

		if ( ! isset($access_upload['href']))
		{
			// $this->parent->setRetries = 1
			throw new \RuntimeException('Не возможно загрузить локальный файл - не получено разрешение.');
		}

		//$stream = new GzipEncode($file_path, 'rb');
		$stream = new Stream($file_path, 'rb');
		$response = $this->parent->send((new Request($access_upload['href'], 'PUT', $stream)));

		$this->emit('disk.uploaded', $this);
		$this->parent->emit('disk.uploaded', $this);

		return $response->getStatusCode() == 201;
	}

}