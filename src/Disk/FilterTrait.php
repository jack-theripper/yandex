<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Disk
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Disk;

/**
 * Опции, доступные при получении списка ресурсов.
 *
 * @package Arhitector\Yandex\Disk
 */
trait FilterTrait
{

	/**
	 * @var array   параметры в запросе
	 */
	protected $parameters = [];

	/**
	 *	@var    bool   были внесены изменения в параметры
	 */
	protected $isModified = false;

	/**
	 * @var array   доступные типы
	 */
	protected $mediaTypes = [

		/**
		 * аудио-файлы
		 */
		'audio',

		/**
		 * файлы резервных и временных копий
		 */
		'backup',

		/**
		 * электронные книги
		 */
		'book',

		/**
		 * сжатые и архивированные файлы
		 */
		'compressed',

		/**
		 * файлы с базами данных
		 */
		'data',

		/**
		 * файлы с кодом (C++, Java, XML и т. п.), а также служебные файлы IDE
		 */
		'development',

		/**
		 * образы носителей информации в различных форматах и сопутствующие файлы (например, CUE)
		 */
		'diskimage',

		/**
		 * документы офисных форматов (Word, OpenOffice и т. п.)
		 */
		'document',

		/**
		 * зашифрованные файлы
		 */
		'encoded',

		/**
		 * исполняемые файлы
		 */
		'executable',

		/**
		 * файлы с флэш-видео или анимацией
		 */
		'flash',

		/**
		 * файлы шрифтов.
		 */
		'font',

		/**
		 * изображения
		 */
		'image',

		/**
		 * файлы настроек для различных программ
		 */
		'settings',

		/**
		 * файлы офисных таблиц (Numbers, Lotus)
		 */
		'spreadsheet',

		/**
		 * текстовые файлы
		 */
		'text',

		/**
		 * неизвестный тип
		 */
		'unknown',

		/**
		 * видео-файлы
		 */
		'video',

		/**
		 * различные файлы, используемые браузерами и сайтами (CSS, сертификаты, файлы закладок)
		 */
		'web'
	];


	/**
	 * Тип ресурса
	 *
	 * @param    string $type
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setType($type)
	{
		if ( ! is_string($type) || ! in_array($type, ['file', 'dir']))
		{
			throw new \UnexpectedValueException('Тип ресурса, допустимые значения - "file", "dir".');
		}

		$this->isModified = true;
		$this->parameters['type'] = $type;

		return $this;
	}

	/**
	 * Относительный путь к ресурсу внутри публичной папки
	 *
	 * @param    string $path
	 *
	 * @return    $this
	 */
	public function setRelativePath($path)
	{
		if ( ! is_string($path))
		{
			throw new \InvalidArgumentException('Относительный путь к ресурсу должен быть строкового типа.');
		}

		$this->isModified = true;
		$this->parameters['path'] = '/'.ltrim($path, ' /');

		return $this;
	}

	/**
	 * Тип файлов, которые нужно включить в список
	 *
	 * @param    string $media_type
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setMediaType($media_type)
	{
		$media_type = (string) $media_type;

		if ( ! in_array($media_type, $this->getMediaTypes()))
		{
			throw new \UnexpectedValueException('Тип файла, значения - "'.implode('", "', $this->getMediaTypes()).'".');
		}

		$this->isModified = true;
		$this->parameters['media_type'] = $media_type;

		return $this;
	}

	/**
	 * Все возможные типы файлов
	 *
	 * @return array
	 */
	public function getMediaTypes()
	{
		return $this->mediaTypes;
	}

	/**
	 * Обрезать превью согласно размеру
	 *
	 * @param    boolean $crop
	 *
	 * @return    $this
	 */
	public function setPreviewCrop($crop)
	{
		$this->isModified = true;
		$this->parameters['preview_crop'] = (bool) $crop;

		return $this;
	}

	/**
	 * Размер уменьшенного превью файла
	 *
	 * @param    mixed $preview S, M, L, XL, XXL, XXXL, <ширина>, <ширина>x, x<высота>, <ширина>x<высота>
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setPreview($preview)
	{
		if (is_scalar($preview))
		{
			$preview = strtoupper($preview);
			$previewNum = str_replace('X', '', $preview, $replaces);

			if (in_array($preview, ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']) || (is_numeric($previewNum) && $replaces < 2))
			{
				if (is_numeric($previewNum))
				{
					$preview = strtolower($preview);
				}

				$this->isModified = true;
				$this->parameters['preview_size'] = $preview;

				return $this;
			}
		}

		throw new \UnexpectedValueException('Допустимые значения размера превью - S, M, L, XL, XXL, XXXL, <ширина>, <ширина>x, x<высота>, <ширина>x<высота>');
	}

	/**
	 * Атрибут, по которому сортируется список ресурсов, вложенных в папку.
	 *
	 * @param    integer $sort
	 * @param    boolean $inverse TRUE чтобы сортировать в обратном порядке
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setSort($sort, $inverse = false)
	{
		$sort = (string) $sort;

		if ( ! in_array($sort, ['name', 'path', 'created', 'modified', 'size', 'deleted']))
		{
			throw new \UnexpectedValueException('Допустимые значения сортировки - name, path, created, modified, size');
		}

		if ($inverse)
		{
			$sort = '-'.$sort;
		}

		$this->isModified = true;
		$this->parameters['sort'] = $sort;

		return $this;
	}

	/**
	 * Количество ресурсов, вложенных в папку, описание которых следует вернуть в ответе
	 *
	 * @param    integer $limit
	 * @param    integer $offset установить смещение
	 *
	 * @return   $this
	 */
	public function setLimit($limit, $offset = null)
	{
		if (filter_var($limit, FILTER_VALIDATE_INT) === false)
		{
			throw new \InvalidArgumentException('Параметр "limit" должен быть целым числом.');
		}

		$this->isModified = true;
		$this->parameters['limit'] = (int) $limit;

		if ($offset !== null)
		{
			$this->setOffset($offset);
		}

		return $this;
	}

	/**
	 * Количество вложенных ресурсов с начала списка, которые следует опустить в ответе
	 *
	 * @param    integer $offset
	 *
	 * @return    $this
	 */
	public function setOffset($offset)
	{
		if (filter_var($offset, FILTER_VALIDATE_INT) === false)
		{
			throw new \InvalidArgumentException('Параметр "offset" должен быть целым числом.');
		}

		$this->isModified = true;
		$this->parameters['offset'] = (int) $offset;

		return $this;
	}

	/**
	 * Возвращает все параметры
	 *
	 * @return array
	 */
	public function getParameters(array $allowed = null)
	{
		if ($allowed !== null)
		{
			return array_intersect_key($this->parameters, array_flip($allowed));
		}

		return $this->parameters;
	}

	/**
	 *	Контейнер изменил состояние
	 *
	 *	@return  bool
	 */
	protected function isModified()
	{
		return (bool) $this->isModified;
	}

}