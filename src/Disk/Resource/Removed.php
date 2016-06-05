<?php

/**
 * Created by Arhitector.
 * Date: 02.03.2016
 * Time: 7:44
 */

namespace Arhitector\Yandex\Disk\Resource;


use Arhitector\Yandex\Client\Container;
use Arhitector\Yandex\Disk\AbstractResource;
use Zend\Diactoros\Request;

class Removed extends AbstractResource
{

	/**
	 * Конструктор.
	 *
	 * @param string|array                   $path путь к ресурсу в корзине
	 * @param \Mackey\Yandex\Disk            $parent
	 * @param \Psr\Http\Message\UriInterface $uri
	 */
	public function __construct($path, \Arhitector\Yandex\Disk $parent, \Psr\Http\Message\UriInterface $uri)
	{
		if (is_array($path))
		{
			if (empty($path['path']))
			{
				throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
			}

			$this->setContents($path);
			$path = $path['path'];
		}

		if ( ! is_scalar($path))
		{
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $path;
		$this->parent = $parent;
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
		if ( ! $this->_toArray() || $this->isModified())
		{
			$response = $this->parent->send((new Request($this->uri->withPath($this->uri->getPath().'trash/resources')
			                                                       ->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
				                                                       'path' => $this->getPath()
			                                                       ]), null, '&')), 'GET')));

			if ($response->getStatusCode() == 200)
			{
				$response = json_decode($response->getBody(), true);

				if ( ! empty($response))
				{
					$this->isModified = false;

					if (isset($response['_embedded'], $response['_embedded']['items']))
					{
						$response += [
								'items' => new Container(array_map(function($item) {
									return new self($item, $this->parent, $this->uri);
								}, $response['_embedded']['items']))
							] + $response['_embedded'];
					}

					unset($response['_links'], $response['_embedded']);

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
		if (is_bool($name))
		{
			$overwrite = $name;
			$name = null;
		}

		if ($name instanceof Closed)
		{
			$name = $name->getPath();
		}

		if ( ! empty($name) && ! is_string($name))
		{
			throw new \InvalidArgumentException('Новое имя для восстанавливаемого ресурса должо быть строкой');
		}

		$request = new Request($this->uri->withPath($this->uri->getPath().'trash/resources/restore')
		                                 ->withQuery(http_build_query([
			                                 'path'      => $this->getPath(),
			                                 'name'      => (string) $name,
			                                 'overwrite' => (bool) $overwrite
		                                 ], null, '&')), 'PUT');

		$response = $this->parent->send($request);

		if ($response->getStatusCode() == 201 || $response->getStatusCode() == 202)
		{
			$this->setContents([]);

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

			return $this->parent->resource($name);
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
		try
		{
			$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'trash/resources')
			                                                      ->withQuery(http_build_query(['path' => $this->getPath()], null, '&')), 'DELETE'));

			if ($response->getStatusCode() == 202)
			{
				$this->setContents([]);
				$response = json_decode($response->getBody(), true);

				if ( ! empty($response['operation']))
				{
					$this->emit('disk.operation', $this);
					$this->parent->emit('disk.operation', $this);

					return $response['operation'];
				}

				return true;
			}

			return $response->getStatusCode() == 204;
		}
		catch (\Exception $exc)
		{
			return false;
		}
	}

}