<?php

namespace Arhitector\Yandex\Exception;

use Arhitector\Yandex\Exception;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Thrown when there is a client error (4xx)
 *
 * @package Arhitector\Yandex\Client\Exception
 */
class ClientException extends Exception implements ClientExceptionInterface
{

}
