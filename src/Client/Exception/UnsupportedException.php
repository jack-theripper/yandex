<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Globosphere\Yandex\Client\Exception
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Globosphere\Yandex\Client\Exception;

use Http\Client\Exception;

/**
 * Исключение некорректные данные.
 */
class UnsupportedException extends \RuntimeException implements Exception
{

}