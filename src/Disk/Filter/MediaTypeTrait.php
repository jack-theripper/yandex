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
 * Аyдио-файлы.
 */
const MEDIA_TYPE_AUDIO = 'audio';
/**
 * Файлы резервных и временных копий.
 */
const MEDIA_TYPE_BACKUP = 'backup';
/**
 * Электронные книги.
 */
const MEDIA_TYPE_BOOK = 'book';
/**
 * Сжатые и архивированные файлы.
 */
const MEDIA_TYPE_ZIP = 'compressed';
/**
 * Файлы с базами данных.
 */
const MEDIA_TYPE_DATA = 'data';
/**
 * Файлы с кодом (C++, Java, XML и т. п.), а также служебные файлы IDE.
 */
const MEDIA_TYPE_SRC = 'development';
/**
 * Образы носителей информации и сопутствующие файлы (например, ISO и CUE).
 */
const MEDIA_TYPE_DISKIMAGE = 'diskimage';
/**
 * Документы офисных форматов (Word, OpenOffice и т. п.).
 */
const MEDIA_TYPE_DOC = 'document';
/**
 * Зашифрованные файлы.
 */
const MEDIA_TYPE_ENCODED = 'encoded';
/**
 * Исполняемые файлы.
 */
const MEDIA_TYPE_EXE = 'executable';
/**
 * Файлы с флэш-видео или анимацией.
 */
const MEDIA_TYPE_FLASH = 'flash';
/**
 * Файлы шрифтов.
 */
const MEDIA_TYPE_FONT = 'font';
/**
 * Изображения.
 */
const MEDIA_TYPE_IMG = 'image';
/**
 * файлы настроек для различных программ.
 */
const MEDIA_TYPE_SETTINGS = 'settings';
/**
 * Файлы офисных таблиц (Excel, Numbers, Lotus).
 */
const MEDIA_TYPE_TABLE = 'spreadsheet';
/**
 * Текстовые файлы.
 */
const MEDIA_TYPE_TEXT = 'text';
/**
 * Неизвестный тип.
 */
const MEDIA_TYPE_UNKNOWN = 'unknown';
/**
 * Видео-файлы.
 */
const MEDIA_TYPE_VIDEO = 'video';
/**
 * Различные файлы, используемые браузерами и сайтами (CSS, сертификаты, файлы закладок).
 */
const MEDIA_TYPE_WEB = 'web';

/**
 * Фильтрация по медиа типам.
 *
 * @package Arhitector\Yandex\Disk\Filter
 */
trait MediaTypeTrait
{
	/**
	 * @var string[] все доступные медиа типы
	 */
	protected $mediaTypes = [
		MEDIA_TYPE_AUDIO,
		MEDIA_TYPE_BACKUP,
		MEDIA_TYPE_BOOK,
		MEDIA_TYPE_ZIP,
		MEDIA_TYPE_DATA,
		MEDIA_TYPE_SRC,
		MEDIA_TYPE_DISKIMAGE,
		MEDIA_TYPE_DOC,
		MEDIA_TYPE_ENCODED,
		MEDIA_TYPE_EXE,
		MEDIA_TYPE_FLASH,
		MEDIA_TYPE_FONT,
		MEDIA_TYPE_IMG,
		MEDIA_TYPE_SETTINGS,
		MEDIA_TYPE_TABLE,
		MEDIA_TYPE_TEXT,
		MEDIA_TYPE_UNKNOWN,
		MEDIA_TYPE_VIDEO,
		MEDIA_TYPE_WEB,
	];

	/**
	 * Тип файлов, которые нужно включить в список.
	 *
	 * @param string $media_type медиа тип (`MEDIA_TYPE_*` константы) или несколько типов перечисленных через запятую
	 * @return $this
	 * @throws \UnexpectedValueException
	 */
	public function setMediaType($media_type)
	{
		$media_types = explode(',', $media_type);
		if (array_diff($media_types, $this->getMediaTypes())) {
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
