<?php
/**
 *	Контейнер данных
 *
 *	@package    Mackey\DataContainer
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\DataContainer;

/**
 *	Общий контейнер данных
 *
 *	@package	Mackey
 *	@subpackage	DataContainer
 */
class Container implements \ArrayAccess, \IteratorAggregate, \Countable
{
	use ContainerTrait;

	/**
	 *	Конструктор
	 *
	 *	@param  array    $data      данные
	 *	@param  boolean  $readOnly  только для чтения
	 */
	public function __construct(array $data = array(), $readOnly = false)
	{
		$this->data = $data;
		$this->readOnly = $readOnly;
	}
}