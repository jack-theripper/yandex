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
namespace Arhitector\Yandex\Disk\Filter;

/**
 * Медиа тип.
 *
 * @package Arhitector\Yandex\Disk\Filter
 */
trait MediaTypeTrait
{

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
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getMediaType()
	{
		if (isset($this->parameters['media_type']))
		{
			return $this->parameters['media_type'];
		}

		return null;
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

}