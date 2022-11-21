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

use Arhitector\Yandex\Client\Container\ContainerTrait;
use League\Event\EmitterTrait;

/**
 * Базовый класс, описывающий ресурс.
 *
 * @package Arhitector\Yandex\Disk
 */
abstract class AbstractResource implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use ContainerTrait, FilterTrait, EmitterTrait {
		toArray as protected _toArray;
		has as hasProperty;
	}

	/**
	 * @var string  Путь к ресурсу.
	 */
	protected $path;

	/**
	 * @var \Psr\Http\Message\UriInterface
	 */
	protected $uri;

	/**
	 * @var \Arhitector\Yandex\Disk объект диска, породивший ресурс.
	 */
	protected $client;

	/**
	 * @var array   допустимые фильтры.
	 */
	protected $parametersAllowed = ['limit', 'offset', 'preview_crop', 'preview_size', 'sort'];


	/**
	 * Есть такой файл/папка на диске или свойство
	 *
	 * @param   mixed   $index
	 *
	 * @return bool
	 */
	public function has($index = null)
	{
		try
		{
			if ($this->toArray())
			{
				if ($index === null)
				{
					return true;
				}

				return $this->hasProperty($index);
			}
		}
		catch (\Exception $exc)
		{

		}

		return false;
	}

	/**
	 * Проверяет, является ли ресурс файлом
	 *
	 * @return bool
	 */
	public function isFile()
	{
		return $this->get('type', false) === 'file';
	}

	/**
	 * Проверяет, является ли ресурс папкой
	 *
	 * @return bool
	 */
	public function isDir()
	{
		return $this->get('type', false) === 'dir';
	}

	/**
	 * Проверяет, этот ресурс с открытым доступом или нет
	 *
	 * @return boolean
	 */
	public function isPublish()
	{
		return $this->has('public_key');
	}

	/**
	 * Получить путь к ресурсу
	 *
	 * @return    string
	 */
	public function getPath()
	{
		return $this->path;
	}

}