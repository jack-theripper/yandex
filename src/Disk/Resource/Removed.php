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
use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Disk\AbstractResource;
use Laminas\Diactoros\Request;

/**
 * Ресурс в корзине.
 *
 * @property-read   string     $name
 * @property-read   string     $created
 * @property-read   string     $deleted
 * @property-read   array|null $custom_properties
 * @property-read   string     $origin_path
 * @property-read   string     $modified
 * @property-read   string     $media_type
 * @property-read   string     $path
 * @property-read   string     $md5
 * @property-read   string     $type
 * @property-read   string     $mime_type
 * @property-read   integer    $size
 *
 * @package Arhitector\Yandex\Disk\Resource
 */
class Removed extends AbstractResource
{

	/**
	 * Конструктор.
	 *
	 * @param string|array                   $path путь к ресурсу в корзине
	 * @param \Arhitector\Yandex\Disk        $parent
	 * @param \Psr\Http\Message\UriInterface $uri
	 */
	public function __construct($path, Disk $parent, \Psr\Http\Message\UriInterface $uri)
	{
		if (is_array($path)) {
			if (empty($path['path'])) {
				throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
			}

			$this->setContents($path);
			$path = $path['path'];
		}

		if (!is_scalar($path)) {
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $path;
		$this->client = $parent;
		$this->uri = $uri;
		$this->setSort('created');
	}

	/**
	 * Получает информацию о ресурсе
	 *
	 * @return    mixed
	 */
	public function toArray(array $allowed = null)
	{
		if (!$this->_toArray() || $this->isModified()) {
			$response = $this->client->send((new Request($this->uri->withPath($this->uri->getPath() . 'trash/resources')
				->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
					'path' => $this->getPath()
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
				}
			}
		}

		return $this->_toArray($allowed);
	}

	/**
	 *	Восстановление файла или папки из Корзины
	 *	В корзине файлы с одинаковыми именами в действительности именют постфикс к имени в виде unixtime
	 *
	 *	@param	mixed	$name	оставляет имя как есть и если boolean это заменяет overwrite
	 *	@param	boolean	$overwrite
	 *	@return	mixed
	 */
	public function restore($name = null, $overwrite = false)
	{
		if (is_bool($name)) {
			$overwrite = $name;
			$name = null;
		}

		if ($name instanceof Closed) {
			$name = $name->getPath();
		}

		if (!empty($name) && !is_string($name)) {
			throw new \InvalidArgumentException('Новое имя для восстанавливаемого ресурса должо быть строкой');
		}

		$request = new Request($this->uri->withPath($this->uri->getPath() . 'trash/resources/restore')
			->withQuery(http_build_query([
				'path'      => $this->getPath(),
				'name'      => (string) $name,
				'overwrite' => (bool) $overwrite
			], '', '&')), 'PUT');

		$response = $this->client->send($request);

		if ($response->getStatusCode() == 201 || $response->getStatusCode() == 202) {
			$this->setContents([]);

			if ($response->getStatusCode() == 202) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['operation'])) {
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}
			}

			return $this->client->getResource($name);
		}

		return false;
	}

	/**
	 * Удаление файла или папки
	 *
	 * @return    mixed
	 */
	public function delete()
	{
		try {
			$response = $this->client->send(new Request($this->uri->withPath($this->uri->getPath() . 'trash/resources')
				->withQuery(http_build_query([
					'path' => $this->getPath()
				], '', '&')), 'DELETE'));

			if ($response->getStatusCode() == 202) {
				$this->setContents([]);
				$response = json_decode($response->getBody(), true);

				if (!empty($response['operation'])) {
					$response['operation'] = $this->client->getOperation($response['operation']);
					$this->emit('operation', $response['operation'], $this, $this->client);
					$this->client->emit('operation', $response['operation'], $this, $this->client);

					return $response['operation'];
				}

				return true;
			}

			return $response->getStatusCode() == 204;
		} catch (\Exception $exc) {
			return false;
		}
	}
}
