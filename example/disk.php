<?php

/**
 * Created by Arhitector.
 * Date: 05.06.2016
 * Time: 17:30
 */

error_reporting(E_ALL);

require_once __DIR__.'/../vendor/autoload.php';

$disk = new Arhitector\Yandex\Disk();
$disk->setAccessToken('');

/**
 * Список всех файлов на диске, метод getResources([int limit = 20, [int offset = 0]].
 * Возвращает объект "Arhitector\Yandex\Disk\Resource\Collection"
 */
/*$resources = $disk->getResources()
	->setLimit(10) // количество файлов, getResources может принять "limit" первым параметром.
	->setOffset(0) // смещениеб пуеКуыщгксуы vj;tn ghbyznm "offset" вторым параметром.
	->setMediaType('image') // мультимедиа тип файлов, все типы $resources->getMediaTypes()
	->setPreview('100x250') // размер превью изображений
	->setPreviewCrop(true) // обрезать превью согласно размеру
	->setSort('name', true) // способ сортировки, второй параметр TRUE означает "в обратном порядке"
	;*/

//$first = $resources->getFirst(); // Arhitector\Yandex\Disk\Resource\Closed
//$last = $resources->getLast(); // Arhitector\Yandex\Disk\Resource\Closed

//var_dump($first->toArray(), $last->toArray(), $resources->toArray());

/**
 * Работа с одним ресурсом, метод "getResource"
 * первым параметром принимает путь к ресурсу - папке или файлу.
 * вторым и третим принимает "limit" и "offset", если ресурс - это папка.
 */
//$resource = $disk->getResource('app:/picture.jpg');

//$has = $resource->has(); // проверить есть ли ресурс на диске.

/*if ( ! $has)
{

	// загружает локальный файл на диск. второй параметр отвечает за перезапись файла, если такой есть на диске.
	// загружает удаленный файл на диск, передайте ссылку http на удаленный файл.
	$resource->upload(__DIR__.'/image.jpg');
}*/

//$has_moved = $resource->move('image2.jpg'); // переместить, boolean
//$result = $resource->upload('http://<..>file.zip');
//var_dump($result->getStatus(), $result->getIdentifier());

//$resource->custom_index_1 = 'value'; // добавить метаинформацию в "custom_properties"
//$resource->set('custom_index_2', 'value'); // добавить метаинформацию в "custom_properties"
//$resource['custom_index_3'] = 'value'; // добавить метаинформацию в "custom_properties"
//unset($resource['custom_index_3']); // удалить метаинформацию, или передать NULL в "set" чтобы удалить

//echo '<a href="'.$resource->docviewer.'" target="_blank">View</a>';

//$resource->download(__DIR__.'/image_downloaded.jpg'); // скачать файл с диска

//var_dump($has, $resource->getProperties(), $resource->toObject());
//$resource = $disk->getPublishResource('https://yadi.sk/d/WSS6bK_ksQ5ck');
//var_dump($resource->items->get(0)->getLink(), $resource->toArray());
//var_dump($resource->download(__DIR__.'/down.zip', true));
//var_dump($resource->items->getFirst()->toArray(), $resource->toArray());

//$resources = $disk->getTrashResources(5);
//$resources = $disk->getTrashResource('trash:/image2.jpg');
//var_dump($resources->toArray());

/*
$disk->addListener('operation', function ($event, $operation) {
	var_dump($operation->getStatus(), func_get_args());
});*/

//var_dump($disk->toArray());

//$resources = $disk->getResources();
//$resources->setMediaType('video');

//$resources = $disk->getTrashResources();
//$resources = $disk->cleanTrash();
//$resources = $disk->uploaded();

//$resources = $disk->getResource('disk:/applu.mp3');
//$resources = $disk->getResource('rich.txt');

//$resources = $disk->getTrashResources(1);
//$resources->setSort('deleted', true);
//$resources->setLimit(1, 1);

//$resources = $disk->getTrashResource('/', 10);
//$resources->setSort('deleted', false);

/*$resources->addListener('operation', function () {
	var_dump('$resources', func_get_args());
});*/

//$result = $resources->create();
//$result = $resources->delete(false);
//$resources->set('nama', rand());
//$resources->rollin = 'bakc';
//unset($resources['rollin']);
//echo $resources->docviewer;
//var_dump($disk->getOperation('0ec0c6a835b72b8860261cc2d5aaf26968b83b7f8eac3118b240eedd')->getStatus());
//$result = $resources->move('rich');
//$result = $resources->setPublish(true);

//$result = $resources->download(__DIR__.'/data_big');
//$result = $resources->upload(__DIR__.'/data_big.data', true);
//var_dump($result, $resources->toArray());
//var_dump($resources->toArray());
//var_dump($disk);