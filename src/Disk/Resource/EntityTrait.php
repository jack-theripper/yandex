<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arhitector\Yandex\Disk\Resource;

use Arhitector\Yandex\Entity;
use RuntimeException;

/**
 * Getters/Setter for entity.
 *
 * @package Arhitector\Yandex\Disk\Resource
 */
trait EntityTrait
{

    /**
     * @var Entity A model that represents information about a resource.
     */
    protected $entity;

    /**
     * Getting and returns the current entity. If needed it will be refresh from api.
     *
     * @return Entity
     */
    abstract public function getEntity(): Entity;

    /**
     * Getting and returns the current entity. If needed it will be refresh from api.
     *
     * @return Entity
     */
    abstract protected function createEntity(): Entity;

    /**
     * Proxy wrapper over entity model.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->getEntity(), $name))
        {
            return call_user_func_array([$this->getEntity(), $name], $arguments);
        }

        throw new RuntimeException(sprintf('Call to undefined method %s::%s()', __CLASS__, $name));
    }

    /**
     * Return an entity or create one.
     *
     * @return Entity
     */
    protected function getOrCreateEntity(): Entity
    {
        // @todo
        // The request should be modified? If so you need to update the Entity
        // If the isModified method does not exist, the entity will never be updated
        if ( ! $this->entity || (method_exists($this, 'isModified') && $this->isModified()))
        {
            $this->entity = $this->createEntity();
        }

        return $this->entity;
    }

    /**
     * Sets a entity object
     *
     * @param Entity $entity [optional] New entity object or `null` to clear current entity
     *
     * @return static
     */
    protected function setEntity(?Entity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

}
