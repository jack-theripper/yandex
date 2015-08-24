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

/**
 *	Интерфейс для работе с ресурсом
 *
 *	@package	Mackey\Yandex\Disk
 */
trait FilterTrait
{
	/**
	 *	@var	array	Параметры
	 */
	protected $request_params = array(
		/**
		 *	количество ресурсов, вложенных в папку, описание которых следует вернуть в ответе
		 */
		'limit'	=> 20,
		
		/**
		 *	ктрибут, по которому сортируется список ресурсов, вложенных в папку
		 */
		'sort'	=> 'name',
		
		/**
		 *	количество вложенных ресурсов с начала списка, которые следует опустить в ответе
		 */
		'offset' => 0,
		
		/**
		 *	размер превью файла S, M, L, XL, XXL, XXXL, ширина 120 или 120x, высота x140, точный размер <ширина>x<высота> 120x140
		 */
		'preview_size' => 'S',

		/**
		 *	позволяет обрезать превью согласно размеру
		 */
		'preview_crop' => false
	);
	
	/**
	 *	Предыдущие параметры запроса
	 */
	protected $previous_params = array();
	
	/**
	 *	Количество ресурсов, вложенных в папку, описание которых следует вернуть в ответе
	 *
	 *	@param	integer	$limit	если NULL получает текущее значение
	 *	@param	integer	$offset	установить начало
	 *	@return	mixed
	 */
	public function limit($limit = null, $offset = null)
	{
		if ($limit === null)
		{
			return $this->request_params['limit'];
		}

		$this->request_params['limit'] = (int) $limit;
		
		if ($offset !== null)
		{
			$this->offset($offset);
		}
		
		return $this;
	}
	
	/**
	 *	Количество вложенных ресурсов с начала списка, которые следует опустить в ответе
	 *
	 *	@param	integer	$offset	если NULL получает текущее значение
	 *	@return	mixed
	 */
	public function offset($offset = null)
	{
		if ($offset === null)
		{
			return $this->request_params['offset'];
		}
		
		$this->request_params['offset'] = (int) $offset;
		
		return $this;
	}
	
	/**
	 *	Атрибут, по которому сортируется список ресурсов, вложенных в папку.
	 *
	 *	@param	integer	$sort		если NULL получает текущее значение
	 *	@param	boolean $inverse	TRUE чтобы сортировать в обратном порядке
	 *	@return	mixed
	 *	@throws	\UnexpectedValueException
	 */
	public function sorting($sort = null, $inverse = false)
	{
		if ($sort === null)
		{
			return $this->request_params['sort'];
		}
		
		$sort = (string) $sort;

		if ( ! in_array($sort, ['name', 'path', 'created', 'modified', 'size', 'deleted']))
		{
			throw new \UnexpectedValueException('Допустимые значения сортировки - name, path, created, modified, size');
		}
		
		if ($inverse)
		{
			$sort = '-'.$sort;
		}

		$this->request_params['sort'] = $sort;
		
		return $this;
	}

	/**
	 *	Размер уменьшенного превью файла
	 *
	 *	@param	mixed	$preview	NULL чтобы получить текущее значение либо S, M, L, XL, XXL, XXXL, <ширина>, x<высота>, <ширина>x<высота>
	 *	@return	mixed
	 *	@throws	\UnexpectedValueException
	 */
	public function preview($preview = null)
	{
		if ($preview === null)
		{
			return $this->request_params['preview_size'];
		}

		if (is_string($preview))
		{
			$count_replace = 0;
			$preview = strtoupper($preview);
			
			if (in_array($preview, ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']) or (is_numeric(str_replace('X', '', $preview, $count_replace)) && $count_replace < 2))
			{

				if(is_numeric(str_replace('X', '', $preview))) {
					$preview = strtolower($preview);
				}

				$this->request_params['preview_size'] = $preview;

				return $this;
			}
		}
		
		throw new \UnexpectedValueException('Допустимые значения размера превью - S, M, L, XL, XXL, XXXL, <ширина>, x<высота>, <ширина>x<высота>');
	}
	
	/**
	 *	Обрезать превью согласно размеру
	 *
	 *	@param	mixed	$preview	NULL или boolean
	 *	@return	mixed
	 */
	public function preview_crop($crop = null)
	{
		if ($crop === null)
		{
			return $this->request_params['preview_crop'];
		}
		
		$this->request_params['preview_crop'] = (bool) $crop;
		
		return $this;
	}

	/**
	 *	Тип файлов, которые нужно включить в список
	 *
	 *	@param	string	$media_type
	 *	@return	mixed
	 *	@throws	\UnexpectedValueException
	 */
	public function media($media_type = null)
	{
		if ($media_type === null)
		{
			return $this->request_params['media_type'];
		}
		
		$media_type = (string) $media_type;

		if ( ! in_array($media_type, ['audio', 'backup', 'book', 'compressed', 'data', 'development', 'diskimage', 'document', 'encoded', 'executable',
			'flash', 'font', 'image', 'settings', 'spreadsheet', 'text', 'unknown', 'video', 'web']))
		{
			throw new \UnexpectedValueException('Допустимые значения - audio, backup, book, compressed, data, development, diskimage, document, encoded,
				executable, flash, font, image, settings, spreadsheet, text, unknown, video, web');
		}

		$this->request_params['media_type'] = $media_type;
		
		return $this;
	}
	
	/**
	 *	Относительный путь к ресурсу внутри публичной папки
	 *
	 *	@param	string	$path	если NULL получает текущее значение
	 *	@return	mixed
	 */
	public function path($path = null)
	{
		if ($path === null)
		{
			return $this->request_params['path'];
		}
		
		$this->request_params['path'] = '/'.ltrim((string) $path, ' /');
		
		return $this;
	}
	
	/**
	 *	Тип ресурса
	 *
	 *	@param	string	$type	если NULL получает текущее значение
	 *	@return	mixed
	 *	@throws	\UnexpectedValueException
	 */
	public function type($type = null)
	{
		if ($type === null)
		{
			return $this->request_params['type'];
		}
		
		$type = (string) $type;

		if ( ! in_array($type, ['file', 'dir']))
		{
			throw new \UnexpectedValueException('Допустимые значения - file, dir');
		}

		$this->request_params['type'] = $type;
		
		return $this;
	}
}