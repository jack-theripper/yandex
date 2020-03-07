<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Client\Exception
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */

namespace Arhitector\Yandex\Client\Exception;

use Arhitector\Yandex\Exception\ClientException;
use Http\Client\Exception;

/**
 * Access is denied for various reasons (403).
 */
class ForbiddenException extends ClientException
{

}
