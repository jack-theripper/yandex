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
 * Фильтрация по медиа типам.
 *
 * @package Arhitector\Yandex\Disk\Filter
 */
trait MediaTypeTrait
{
	/**
	 * Аyдио-файлы.
	 */
	public const TYPE_AUDIO = 'audio';
	/**
	 * Файлы резервных и временных копий.
	 */
	public const TYPE_BACKUP = 'backup';
	/**
	 * Электронные книги.
	 */
	public const TYPE_BOOK = 'book';
	/**
	 * Сжатые и архивированные файлы.
	 */
	public const TYPE_ZIP = 'compressed';
	/**
	 * Файлы с базами данных.
	 */
	public const TYPE_DATA = 'data';
	/**
	 * Файлы с кодом (C++, Java, XML и т. п.), а также служебные файлы IDE.
	 */
	public const TYPE_SRC = 'development';
	/**
	 * Образы носителей информации и сопутствующие файлы (например, ISO и CUE).
	 */
	public const TYPE_DISKIMAGE = 'diskimage';
	/**
	 * Документы офисных форматов (Word, OpenOffice и т. п.).
	 */
	public const TYPE_DOC = 'document';
	/**
	 * Зашифрованные файлы.
	 */
	public const TYPE_ENCODED = 'encoded';
	/**
	 * Исполняемые файлы.
	 */
	public const TYPE_EXE = 'executable';
	/**
	 * Файлы с флэш-видео или анимацией.
	 */
	public const TYPE_FLASH = 'flash';
	/**
	 * Файлы шрифтов.
	 */
	public const TYPE_FONT = 'font';
	/**
	 * Изображения.
	 */
	public const TYPE_IMG = 'image';
	/**
	 * файлы настроек для различных программ.
	 */
	public const TYPE_SETTINGS = 'settings';
	/**
	 * Файлы офисных таблиц (Excel, Numbers, Lotus).
	 */
	public const TYPE_TABLE = 'spreadsheet';
	/**
	 * Текстовые файлы.
	 */
	public const TYPE_TEXT = 'text';
	/**
	 * Неизвестный тип.
	 */
	public const TYPE_UNKNOWN = 'unknown';
	/**
	 * Видео-файлы.
	 */
	public const TYPE_VIDEO = 'video';
	/**
	 * Различные файлы, используемые браузерами и сайтами (CSS, сертификаты, файлы закладок).
	 */
	public const TYPE_WEB = 'web';

	/**
	 * @var string[] все доступные медиа типы
	 */
	protected $mediaTypes = [
		self::TYPE_AUDIO,
		self::TYPE_BACKUP,
		self::TYPE_BOOK,
		self::TYPE_ZIP,
		self::TYPE_DATA,
		self::TYPE_SRC,
		self::TYPE_DISKIMAGE,
		self::TYPE_DOC,
		self::TYPE_ENCODED,
		self::TYPE_EXE,
		self::TYPE_FLASH,
		self::TYPE_FONT,
		self::TYPE_IMG,
		self::TYPE_SETTINGS,
		self::TYPE_TABLE,
		self::TYPE_TEXT,
		self::TYPE_UNKNOWN,
		self::TYPE_VIDEO,
		self::TYPE_WEB,
	];

	/**
	 * Тип файлов, которые нужно включить в список.
	 *
	 * @param string $media_type медиа тип (`TYPE_*` константы) или несколько типов перечисленных через запятую
	 * @return $this
	 * @throws \UnexpectedValueException
	 */
	public function setMediaType($media_type)
	{
		$media_types = explode(',', $media_type);
		if (array_diff($this->getMediaTypes(), $media_types)) {
			throw new \UnexpectedValueException(
				'Неверный тип файла, допустимые значения: "'.implode('", "', $this->getMediaTypes()).'".'
			);
		}

		$this->isModified = true;
		$this->parameters['media_type'] = $media_type;

		return $this;
	}

	/**
	 * Получает установленное значение.
	 *
	 * @return string|null
	 */
	public function getMediaType()
	{
		return $this->parameters['media_type'] ?? null;
	}

	/**
	 * Все возможные типы файлов.
	 *
	 * @return string[]
	 */
	public function getMediaTypes()
	{
		return $this->mediaTypes;
	}

}
