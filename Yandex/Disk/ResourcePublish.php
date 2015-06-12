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
 *	Интерфейс для работе с публичными ресурсами
 *
 *	@package	Mackey\Yandex\Disk
 */
class ResourcePublish extends Container
{
	use FilterTrait;
	
	/**
	 *	@var	string	ресурс
	 */
	protected $public_key;
	
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
	public function __construct($public_key, Disk $disk, Request $request)
	{
		if (is_array($public_key))
		{
			if (empty($public_key['public_key']))
			{
				throw new \InvalidArgumentException('Передайте публичный ключ ресурса.');
			}
			
			parent::__construct($public_key, false);
	
			if (isset($public_key['type']))
			{
				$this->resource_type = $public_key['type'];
			}
			
			$public_key = $public_key['public_key'];
		}
		
		$this->public_key = (string) $public_key;
		$this->parent_disk = $disk;
		$this->request = $request;
	}
		
	/**
	 *	Получить публичный ключ
	 *
	 *	@return	string|null
	 */
	public function getPublicKey()
	{
		return $this->public_key;
	}

	/**
	 *	Получает информацию о ресурсе
	 *
	 *	@return	mixed
	 */
	public function getContents()
	{
		if ( ! parent::getContents() or $this->previous_params != $this->request_params)
		{
			$this->previous_params = $this->request_params;
			$response = $this->request
				->get($this->parent_disk->getRequestUrl('public/resources', array_merge($this->request_params, ['public_key' => $this->getPublicKey()])));
			
			if (isset($response['type']))
			{
				$this->resource_type = $response['type'];
			}

			if (isset($response['_embedded'], $response['_embedded']['items']))
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
	 *	Есть такой ресурс или свойство
	 *
	 *	@param	string	$key	или NULL чтобы проверить ресурс
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
	 *	Получает прямую ссылку
	 *
	 *	@return	string
	 *	@throws	mixed
	 */
	public function getLink()
	{
		if ( ! $this->has())
		{
			throw new Exception\NotFoundException('Не возможно скачать, данный ресурс не публичный ?');
		}
		
		$response = $this->request->get($this->parent_disk->getRequestUrl('public/resources/download', array(
				'public_key' => $this->getPublicKey(),
				'path' => (string) $this->path()
			)
		));

		if (empty($response['href']))
		{
			throw new \UnexpectedValueException('Не удалось запросить разрешение на скачивание, повторите заново');
		}
		
		return $response['href'];
	}
	
	/**
	 *	Скачивание публичного файла или папки
	 *
	 *	@param	$path	Путь, по которому будет сохранён файл
	 *	@return	boolean
	 *	@throws	mixed
	 */
	public function download($path, $overwrite = false, $progress = null)
	{
		if ($overwrite instanceof \Closure)
		{
			$progress = $overwrite;
			$overwrite = false;
		}
		
		if (is_file($path) && ! $overwrite)
		{
			throw new \OutOfBoundsException('Такой файл существует, преедайте true Чтобы перезаписать его');
		}
		
		if ( ! is_writable(dirname($path)))
		{
			throw new \OutOfBoundsException('Запись в директорию где должен быть располоен файл не возможна');
		}

		$response = $this->getLink();

		if ($progress instanceof \Closure)
		{
			$this->request->progress(function ($curl, $download, $downloaded, $upload, $uploaded) use ($progress) {
				return $progress($download, $downloaded, $curl);
			});
		}
		
		$this->request->setTimeout(null);
		$this->request->setOpt(CURLOPT_FOLLOWLOCATION, true);
		$this->request->download($response, $path);
		$this->request->setDefaultTimeout();
		$this->request->progress(null);

		if ($this->type == 'file' && md5_file($path) != $this->md5)
		{
			throw new \RangeException('Файл скачан, но контрольные суммы различаются.');
		}
		
		return $this->request->http_status_code == 200;
	}

	/**
	 *	Этот файл или такой же находится на этом диске
	 *
	 *	@return	boolean
	 */
	public function hasEqual()
	{
		if ($this->has() && ($path = $this->get('name')))
		{			
			try
			{
				return $this->parent_disk
					->resource(((string) $this->get('path')).'/'.$path)
					->get('md5', false) === $this->get('md5');
			}
			catch (\Exception $exc) { }
		}
		
		return false;
	}
	
	/**
	 *	Сохранение публичного файла в «Загрузки» или отдельный файл из публичной папки
	 *
	 *	@param	string	$name	Имя, под которым файл следует сохранить в папку «Загрузки»
	 *	@param	string	$path	Путь внутри публичной папки.
	 *	@return	mixed	
	 */
	public function save($name = null, $path = null)
	{
		$params = array();
		
		if (is_string($path))
		{
			$params['path'] = $path;
		}
		
		if (is_string($name))
		{
			$params['name'] = $name;
		}
		else if ($name instanceof Resource)
		{
			$params['name'] = substr(strrchr($name->getPath(), '/'), 1);
		}

		$this->request->post($this->parent_disk->getRequestUrl('public/resources/save-to-disk', array(
				'public_key' => $this->getPublicKey()
			) + $params
		));

		if ( ! empty($response['operation']))
		{
			return $response['operation'];
		}

		return $this->request->http_status_code == 201;
	}

}