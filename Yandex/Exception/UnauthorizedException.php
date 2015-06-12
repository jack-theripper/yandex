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
 *	Исключение не авторизован
 *
 *	@package	Mackey\Yandex\Exception
 */
class UnauthorizedException extends \RuntimeException
{
	/**
	 *	Конструктор.
	 *
	 *	@access  public
	 *	@param   int		$code		Код исключения
	 *	@param   string  	$message	Сообщение исключения
	 *	@param   \Exception $previous	Предыдущее исключение
	 */
	public function __construct($message, $code = 401, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}