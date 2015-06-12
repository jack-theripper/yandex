<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey\Yandex\Exception
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\Yandex\Exception;

/**
 *	Исключение ресурс отсутствует
 *
 *	@package	Mackey\Yandex\Exception
 */
class NotFoundException extends \RuntimeException
{
	/**
	 *	Конструктор.
	 *
	 *	@access  public
	 *	@param   int		$code		Код исключения
	 *	@param   string  	$message	Сообщение исключения
	 *	@param   \Exception $previous	Предыдущее исключение
	 */
	public function __construct($message, $code = null, \Exception $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}