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
 * Тип - файл/папка.
 * 
 * @package Arhitector\Yandex\Disk\Filter
 */
trait TypeTrait
{

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
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getType()
	{
		if (isset($this->parameters['type']))
		{
			return $this->parameters['type'];
		}

		return null;
	}
	
}