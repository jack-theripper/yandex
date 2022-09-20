<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Globosphere\Yandex\Disk
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Globosphere\Yandex\Disk\Filter;

/**
 * Относительный путь к ресурсу внутри публичной папки.
 *
 * @package Globosphere\Yandex\Disk\Filter
 */
trait RelativePathTrait
{

	/**
	 * Относительный путь к ресурсу внутри публичной папки.
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
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getRelativePath()
	{
		if (isset($this->parameters['path']))
		{
			return $this->parameters['path'];
		}

		return null;
	}

}