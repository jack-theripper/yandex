<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey\Yandex\Disk
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\Yandex\Disk;

use \Mackey\DataContainer\Container;
use \Mackey\Yandex\Request;
use \Mackey\Yandex\CiperTrait;
use \Mackey\Yandex\Exception\AlreadyExistsException;

/**
 *	Интерфейс для работе с ресурсом
 *
 *	@package	Mackey\Yandex\Disk
 */
class Resource extends Container
{
	use FilterTrait, CiperTrait;
	
	/**
	 *	@var	string	ресурс
	 */
	protected $resource_path;
	
	/**
	 *	@var	string	имя ресурса в корзине, если он был удалён
	 */
	protected $resource_path_trash;
	
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
	 *	@throws	\InvalidArgumentException
	 */
	public function __construct($path, \Mackey\Yandex\Disk $disk, Request $request)
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
	 *	Получает информацию о ресурсе
	 *
	 *	@return	mixed
	 */
	public function getContents()
	{
		if ( ! parent::getContents() || ($this->previous_params && $this->previous_params != $this->request_params))
		{
			$response = $this->request
				->get($this->parent_disk->getRequestUrl('resources', array_merge($this->request_params, array('path' => $this->resource_path))));
			$this->previous_params = $this->request_params;

			if (isset($response['type']))
			{
				$this->resource_type = $response['type'];
			}

			if (isset($response['_embedded'], $response['_embedded']['items']))
			{
				$response['items'] = array_map(function ($item) {
					return new self($item, $this->parent_disk, $this->request);
				}, $response['_embedded']['items']);

				foreach($response['_embedded'] as $prop => $value) {
					if($prop != 'items') $response[$prop] = $value;
				}
			}
			
			unset($response['_links'], $response['_embedded']);
			parent::__construct($response);
		}
		
		return parent::getContents();
	}

	/**
	 *	Добавление метаинформации для ресурса
	 *
	 *	@param	mixed	$data_set	строка либо массив значений
	 *	@param	mixed	$value		NULL чтобы удалить определённую метаинформаию когда $data_set строка
	 *	@return	$this
	 *	@throws	\OutOfBoundsException
	 */
	public function set($data_set, $value = null)
	{
		if ( ! is_array($data_set))
		{
			$data_set = array((string) $data_set => $value);
		}
		
		if (empty($data_set))
		{
			throw new \OutOfBoundsException('Передайте нормальные значения чтобы добавить метаинформацию для ресурса.');
		}
		
		$this->setContents($this->request->patch($this->parent_disk->getRequestUrl('resources', array('path' => $this->resource_path)), json_encode(array(
				'custom_properties' => $data_set
			))
		));

		return $this;
	}

	/**
	 *	Загрузить файл на диск
	 *
	 *	@param	string	$file_path	может быть как путь к локальному файлу, так и URL к файлу
	 *	@param	mixed	$overwrite
	 *	@param	mixed	$progress
	 *	@return	boolean
	 *	@throws	mixed
	 */
	public function upload($file_path, $overwrite = false, $progress = null)
	{
		if ( ! is_string($file_path))
		{
			throw new \InvalidArgumentException('Параметр "путь к файлу" должен быть строкового типа.');
		}
		
		$scheme = substr($file_path, 0, 7);

		if ($scheme == 'http://' or $scheme == 'https:/')
		{
			try
			{
				$response = $this->request->post($this->parent_disk->getRequestUrl('resources/upload', array(
						'url' => $file_path,
						'path' => $this->resource_path
					))
				);
			}
			catch (AlreadyExistsException $exc) 
			{
				// параметр $overwrite не работает т.к. диск не поддерживает {AlreadyExistsException:409}->rename->delete
				throw new AlreadyExistsException($exc->getMessage().' Перезапись для удалённой загрузки не доступна.', $exc->getCode(), $exc);
			}
			
			if ( ! empty($response['operation']))
			{
				return $response['operation'];
			}
			
			return false;
		}

		if ( ! is_file($file_path))
		{
			throw new \OutOfBoundsException('Локальный файл по такому пути: "'.$file_path.'" отсутствует.');
		}
		
		if ($overwrite instanceof \Closure)
		{
			$progress = $overwrite;
			$overwrite = false;
		}
		
		$access_upload = $this->request->get($this->parent_disk->getRequestUrl('resources/upload', array(
				'path' => $this->resource_path,
				'overwrite' => (int) ((boolean) $overwrite),
			))
		);

		if ( ! isset($access_upload['href']))
		{
			throw new \RuntimeException('Не возможно загрузить локальный файл - не получено разрешение.');
		}

		if ($progress instanceof \Closure)
		{
			$this->request->progress(function ($curl, $download, $downloaded, $upload, $uploaded) use ($progress) {
				return $progress($upload, $uploaded, $curl);
			});
		}

		$put_data = null;
		$file_path = realpath($file_path);
		$this->request->setTimeout(null);
		$this->request->unsetHeader('Content-Type');
		$this->request->unsetHeader('Content-Length');
		$this->request->setOpt(CURLOPT_INFILESIZE, filesize($file_path)); 

		if ($this->encryption())
		{
			/* Ограничение. Ограничение на длину объекта custom_properties 
				(имена и значения вложенных ключей, а также синтаксические знаки) — 1024 символа. */

			$put_data = [''];
			$this->request->setOpt(CURLOPT_UPLOAD, true);
			$this->request->setOpt(CURLOPT_BINARYTRANSFER, true);
			$this->request->setOpt(CURLOPT_INFILE, fopen($file_path, 'rb'));
			$this->request->setOpt(CURLOPT_READFUNCTION, function ($ch, $fh, $length) use (&$block_length) {
				try
				{
					return $this->encrypt(fread($fh, ($block_length = $length)), true);
				}
				catch (\Exception $exc)
				{
					return;
				}
			});
		}

		$this->request->put($access_upload['href'], $put_data ?: ['file' => new \CurlFile($file_path)]);
		$this->request->setOpt(CURLOPT_INFILESIZE, null);
		$this->request->setHeader('Content-Type', $this->parent_disk->contentType());
		$this->request->setDefaultTimeout();
		$this->request->progress(null);
		$this->request->setOpt(CURLOPT_NOPROGRESS, true);
		$result = $this->request->http_status_code;

		if ($this->encryption())
		{
			$this->request->setOpt([
				CURLOPT_UPLOAD => false,
				CURLOPT_BINARYTRANSFER => false,
				CURLOPT_INFILE => null,
				CURLOPT_READFUNCTION => null
			]);
			
			$this->set([
				'encrypted' 		=> md5_file($file_path),
				'encrypted_block'	=> $block_length,
				'encrypted_phrase'	=> (string) $this->phrase(),
				'encrypted_vector'	=> base64_encode($this->vector(false))
			]);
		}

		return $result == 201;		
	}

	/**
	 *	Удаление файла или папки
	 *
	 *	@param	boolean	$permanently	TRUE Признак безвозвратного удаления
	 *	@return	mixed
	 */
	public function delete($permanently = false)
	{
		$result = $this->request->delete($this->parent_disk->getRequestUrl('resources'), array(
			'path' => $this->resource_path,
			'permanently' => (bool) $permanently
		));
		
		if ( ! empty($result['operation']))
		{
			return $result['operation'];
		}
		
		$this->setContents(array());
		$status = $this->request->http_status_code;

		try
		{
			$trash = $this->parent_disk
				->trash(null, 1)
				->sorting('deleted', true)
				->get(0, false);
			
			if ($trash && $trash->get('origin_path') == $this->getPath())
			{
				$this->resource_path_trash = $trash->get('path');
				
				return $trash;
			}
		}
		catch (\Exception $exc) { }
		
		return $status == 204;
	}
	
	/**
	 *	Скачивает файл
	 *
	 *	@param	$path	Путь, по которому будет сохранён файл
	 *	@param	mixed	$overwrite
	 *	@param	mixed	$progress
	 *	@return	boolean
	 *	@throws	mixed
	 */
	public function download($path, $overwrite = false, $progress = null)
	{
		if ( ! $this->has())
		{
			throw new Exception\NotFoundException('Не возможно скачать, данный ресурс отсутствует на диске ?');
		}

		if ($overwrite instanceof \Closure)
		{
			$progress = $overwrite;
			$overwrite = false;
		}
		
		if (is_file($path) && ! $overwrite)
		{
			throw new \OutOfBoundsException('Такой файл существует, передайте true Чтобы перезаписать его');
		}
		
		if ( ! is_writable(dirname($path)))
		{
			throw new \OutOfBoundsException('Запись в директорию где должен быть располоен файл не возможна');
		}

		$response = $this->request->get($this->parent_disk->getRequestUrl('resources/download', array('path' => $this->resource_path)));

		if (empty($response['href']))
		{
			throw new \UnexpectedValueException('Не удалось запросить закачку, повторите заново');
		}
		
		if ($progress instanceof \Closure)
		{
			$this->request->progress(function ($curl, $download, $downloaded, $upload, $uploaded) use ($progress) {
				return $progress($download, $downloaded, $curl);
			});
		}

		$put_data = $path;
		$this->request->setTimeout(null);
		$this->request->setOpt(CURLOPT_FOLLOWLOCATION, true);

		if ($this->hasEncrypted())
		{
			$encrypted = $this->get('custom_properties', []);
			$this->phrase($encrypted['encrypted_phrase'])
				->vector(base64_decode($encrypted['encrypted_vector']));
			$put_data = function ($instance, $fh) use ($path, $encrypted) {
				$fp = fopen($path, 'wb');
				
				while ( ! feof($fh))
				{
					fwrite($fp, $this->decrypt(fread($fh, $encrypted['encrypted_block'])));
				}
				
				fclose($fp);
			};
		}

		$this->request->download($response['href'], $put_data);
		$this->request->setDefaultTimeout();
		$this->request->progress(null);

		if ((isset($encrypted['encrypted']) && (($hash_file = $encrypted['encrypted']) || ($hash_file = $md5))) && $hash_file !== md5_file($path))
		{
			throw new \RangeException('Файл скачан, но контрольные суммы различаются.');
		}

		return $this->request->http_status_code == 200;
	}

	/**
	 *	Копирование файла или папки
	 *
	 *	@param	string|Resource
	 */
	public function copy($path_to, $overwrite = false)
	{
		if ($path_to instanceof Resource)
		{
			$path_to = $path_to->getPath();
		}

		$response = $this->request->post($this->parent_disk->getRequestUrl('resources/copy', array(
				'from' => $this->resource_path,
				'path' => $path_to,
				'overwrite' => (bool) $overwrite
			))
		);

		if ( ! empty($response['operation']))
		{
			return $response['operation'];
		}

		return $this->request->http_status_code == 201;
	}

	/**
	 *	Перемещение файла или папки
	 *
	 *	@param	string|Resource
	 */
	public function move($path_to, $overwrite = false)
	{
		if ($path_to instanceof Resource)
		{
			$path_to = $path_to->getPath();
		}
		else if ($path_to instanceof ResourcePublish)
		{
			return false;
		}
		
		$response = $this->request->post($this->parent_disk->getRequestUrl('resources/move', array(
				'from' => $this->resource_path,
				'path' => $path_to,
				'overwrite' => (bool) $overwrite
			))
		);
		
		$this->resource_path = $path_to;
		
		if ( ! empty($response['operation']))
		{
			return $response['operation'];
		}
			
		return $this->request->http_status_code == 201;
	}
	
	/**
	 *	Публикация ресурса\Закрытие доступа
	 *
	 *	@param	string|Resource
	 */
	public function publish($publish = true)
	{
		$request = 'resources/unpublish';
		
		if ($publish)
		{
			$request = 'resources/publish';
		}
		
		$this->request->put($this->parent_disk->getRequestUrl($request, array('path' => $this->resource_path)));

		if ($this->request->http_status_code == 200)
		{
			$this->setContents(array());

			if ($this->has('public_key'))
			{
				return $this->parent_disk->publish($this->get('public_key'));
			}
		}
		
		return $this;
	}
	
	/**
	 *	Создание папки, если ресурса с таким же именем нет
	 *
	 *	@return	$this
	 *	@throws	mixed
	 */
	public function create()
	{
		try
		{
			$this->request->put($this->parent_disk->getRequestUrl('resources', array('path' => $this->resource_path)));
			$this->setContents(array());
		}
		catch (\Exception $exc)
		{
			// ... на будущее
			throw $exc;
		}

		return $this;
	}
	
	/**
	 *	Восстановление файла или папки из Корзины
	 *	В корзине файлы с одинаковыми именами в действительности именют постфикс к имени в виде unixtime
	 *
	 *	@param	mixed	$name	если boolean это заменяет overwrite
	 *	@param	boolean	$overwrite
	 *	@return	mixed
	 */
	public function restore($name = null, $overwrite = false)
	{
		if ($name instanceof Resource)
		{
			$name = $name->getPath();
		}
		
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
				'path' => $this->getPath(true),
				'name' => (string) $name,
				'overwrite' => (bool) $overwrite
			)
		), '');
		$this->resource_path_trash = null;
		
		if ( ! empty($response['operation']))
		{
			return $response['operation'];
		}
		
		return $this;
	}
	
	/**
	 *	Если файл помещен в корзину, удалить из корзины
	 *
	 *	@return	mixed
	 */
	public function trash()
	{
		if ($this->getPath() === null)
		{
			return false;
		}
		
		return $this->parent_disk
			->trash($this->getPath(true))
			->delete();	
	}
	
	/**
	 *	Получить путь к ресурсу
	 *
	 *	@return	string
	 */
	public function getPath($has_trash = false)
	{
		if ($has_trash)
		{
			return $this->resource_path_trash ?: $this->resource_path;
		}
		
		return $this->resource_path;	
	}
}
