<?php

/**
 * Пример показывает как нестандартный запрос к API Яндекс.Диска.
 */

use Laminas\Diactoros\Request;

require_once __DIR__.'/../vendor/autoload.php';

$token = 'Access Token';

$disk = new Arhitector\Yandex\Disk($token);

// Внимание! В запрос будет передан Access Token
$request = new Request('https://cloud-api.yandex.net/v1/disk/resources?path=O2cXW1AEVWI222.jpg', 'GET');
$response = $disk->send($request);

var_dump($response->getBody()->getContents());

// Но и этот факт можно использовать, например как получить превью закрытого ресурса
$resource = $disk->getResource('disk:/O2cXW1AEVWI222.jpg')
	->setPreview('100x250');

$request = new Request($resource->preview, 'GET');
$response = $disk->send($request);

header('Content-type: image/jpeg');

echo $response->getBody();

