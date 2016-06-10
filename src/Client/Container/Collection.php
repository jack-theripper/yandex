<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Client
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Client\Container;

/**
 * Коллекция на основе контейнера.
 * 
 * @package Arhitector\Yandex\Client\Container
 */
class Collection implements \ArrayAccess, \IteratorAggregate, \Countable
{
	use ContainerTrait;

	/**
	 * Конструктор
	 *
	 * @param  array   $data     данные
	 * @param  boolean $readOnly только для чтения
	 */
	public function __construct(array $data = array(), $readOnly = false)
	{
		$this->setContents($data);
	}

	/**
	 * Получает первый элемент в списке
	 *
	 * @return mixed
	 */
	public function getFirst()
	{
		$elements = $this->toArray();

		return reset($elements);
	}

	/**
	 * Получает последний элемент в списке
	 *
	 * @return  \Arhitector\Yandex\Disk\AbstractResource
	 */
	public function getLast()
	{
		$elements = $this->toArray();

		return end($elements);
	}

}