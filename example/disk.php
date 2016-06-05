<?php

/**
 * Created by Arhitector.
 * Date: 05.06.2016
 * Time: 17:30
 */

error_reporting(E_ALL);

require_once __DIR__.'/../vendor/autoload.php';

$disk = new Arhitector\Yandex\Disk();
$disk->setAccessToken('c760916b09034aff9a6a7ca765eb39b8');

//var_dump($disk->toArray());

//$resources = $disk->getResources();
//$resources = $disk->getTrashResources();
//$resources = $disk->cleanTrash();

$resources = $disk->uploaded();

var_dump($resources->toObject());

var_dump($disk);