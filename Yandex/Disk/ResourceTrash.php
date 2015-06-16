<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\Yandex\Disk;

use \Mackey\DataContainer\Container;
use \Mackey\Yandex\Request;
use \Mackey\Yandex\Disk;

/**
 *	Интерфейс для работе с ресурсом
 *
 *	@package	Yandex
 *	@subpackage	Disk
 */
class ResourceTrash extends Container
{
	use FilterTrait;
	
	/**
	 *	@var	string	ресурс
	 */
	protected $resource_path;
	
	/**
	 *	@var	Yandex\Disk
	 */
	private $parent_disk;
	
	/**
	 *	@var	Yandex\Request
	 */
	private $request;
	
	/**
	 *	@var	string	тип ресурса
	 */
	private $resource_type = null;
	
	/**
	 *	Конструктор
	 *
	 *	@param	string|array	$path	путь к существующему либо будущему ресурсу
	 *	@throws	InvalidArgumentException
	 */
	public function __construct($path, Disk $disk, Request $request)
	{
		if (is_array($path))
		{
			if (empty($path['path']))
			{
				throw new \InvalidArgumentException('Передайте действительный путь к файлу или папке.');
			}
			
			parent::__construct($path, false);			
			$path = $path['path'];
		}

		$this->resource_path = (string) $path;
		$this->parent_disk = $disk;
		$this->request = $request;
		$this->sorting('created');
	}
	
	/**
	 *	Есть такой файл или папка на диске
	 *
	 *	@return	boolean
	 */
	public function has($key = null)
	{
		try
		{
			if ($this->getContents())
			{
				if ($key === null)
				{
					return true;
				}
				
				return parent::has($key);
			}
		}
		catch (\Exception $exc) { }

		return false;
	}
	
	/**
	 *	Получить данные контейнера
	 *
	 *	@return  array  контейнер
	 */
	public function getContents()
	{
		if ( ! parent::getContents())
		{
			$response = $this->request
				->get($this->parent_disk->getRequestUrl('trash/resources', array_merge($this->request_params, array('path' => $this->resource_path))));

			if (isset($response['type']))
			{
				$this->resource_type = $response['type'];
			}

			if (isset($response['_embedded']['items']))
			{
				$response['items'] = array_map(function ($item) {
					return new self($item, $this->parent_disk, $this->request);
				}, $response['_embedded']['items']);
			}

			unset($response['_links'], $response['_embedded']);
			parent::__construct($response);
		}

		return parent::getContents();
	}

	/**
	 *	Удаление файла или папки
	 *
	 *	@return	mixed
	 */
	public function delete($not_use_var = null)
	{
		try
		{
			$response = $this->request->delete($this->parent_disk->getRequestUrl('trash/resources'), array('path' => $this->getPath()));

			if ( ! empty($response['operation']))
			{
				return $response['operation'];
			}

			$this->setContents(array());

			return $this->request->http_status_code == 204;
		}
		catch (\Exception $exc)
		{
			return false;
		}
	}

	/**
	 *	Восстановление файла или папки из Корзины
	 *	В корзине файлы с одинаковыми именами в действительности именют постфикс к имени в виде unixtime
	 *
	 *	@param	mixed	$name	елси boolean это заменяет overwrite
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
		
		if ( ! empty($name) && ! is_string($name))
		{
			throw new \InvalidArgumentException('Новое имя для восстанавливаемого ресурса должо быть строкой');
		}

		$this->request->put($this->parent_disk->getRequestUrl('trash/resources/restore', array(
				'path' => $this->getPath(),
				'name' => (string) $name,
				'overwrite' => (bool) $overwrite
			)
		), '');

		if ( ! empty($response['operation']))
		{
			return $response['operation'];
		}
		
		return $this;
	}
	
	/**
	 *	Получить путь к ресурсу
	 *
	 *	@return	string
	 */
	public function getPath()
	{
		return $this->resource_path;	
	}

}