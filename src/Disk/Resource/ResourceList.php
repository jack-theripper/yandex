<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Disk
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Disk\Resource;

use Arhitector\Yandex\Client\Container\Collection as CollectionContainer;
use Arhitector\Yandex\Disk\AbstractResource;
use Arhitector\Yandex\Disk\Filter\MediaTypeTrait;
use Arhitector\Yandex\Disk\FilterTrait;
use Arhitector\Yandex\Entity;
use Arhitector\Yandex\Entity\PublicResource;
use Closure;

/**
 * Коллекция ресурсов.
 *
 * @package Arhitector\Yandex\Disk\Resource
 */
class ResourceList extends CollectionContainer
{
	use FilterTrait, MediaTypeTrait, EntityTrait;

	/**
	 * @var callable
	 */
	protected $provider;

	/**
	 * @var array   какие параметры доступны для фильтра
	 */
	protected $parametersAllowed = ['limit', 'media_type', 'offset', 'preview_crop', 'preview_size', 'sort'];

	/**
	 *	Конструктор
	 */
	public function __construct(Closure $data_closure = null)
	{
		$this->provider = $data_closure;
	}

    // getFirstItem
    // getLastItem

    /**
     * @inheritDoc
     */
    protected function createEntity(): Entity\PublicResourceList
    {
        $data = call_user_func($this->provider, $this->getParameters($this->parametersAllowed));
        $this->isModified = false;

        return new Entity\PublicResourceList($data);
    }

    /**
     * @inheritDoc
     * @return Entity|Entity\PublicResourceList
     */
    public function getEntity(): Entity
    {
        return $this->getOrCreateEntity();
    }
}
