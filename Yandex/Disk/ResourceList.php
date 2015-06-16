<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey\Yandex\Disk\Resource
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\Yandex\Disk;

use \Mackey\DataContainer\Container;

/**
 *	Клиент для работы с Яндекс-диском
 *
 *	@package	Mackey\Yandex\Disk
 *	@subpackage	Resource
 */
class ResourceList extends Container
{
	use FilterTrait;
	
	/**
	 *	@var	Callable
	 */
	protected $closure;
	
	/**
	 *	Конструктор
	 */
	public function __construct(\Closure $data_closure = null)
	{
		$this->closure = $data_closure;
	}
	
	/**
	 *	Получает информацию
	 *
	 *	@return	array
	 */
	public function getContents()
	{
		if ( ! parent::getContents() or ($this->previous_params && $this->previous_params != $this->request_params))
		{
			parent::__construct(call_user_func($this->closure, $this->request_params), true);
			
			$this->previous_params = $this->request_params;
		}

		return parent::getContents();
	}	
}