<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arhitector\Yandex\Disk;

use Arhitector\Yandex\DiskClient;
use Arhitector\Yandex\Entity;
use Exception;
use League\Event\EmitterTrait;
use RuntimeException;

/**
 * The base class that defines the resource.
 *
 * @package Arhitector\Yandex\Disk
 * @mixin Entity
 */
abstract class AbstractResource /*implements \ArrayAccess*/
{
    use FilterTrait, EmitterTrait;

    /**
     * @var DiskClient The client that spawned the resource.
     */
    protected $client;

    /**
     * @var string Identifier or path of the resource on the disk.
     */
    //protected $resourcePath;

    /**
     * @var Entity A model that represents information about a resource.
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $parametersAllowed = ['limit', 'offset', 'preview_crop', 'preview_size', 'sort'];

    /**
     * Returns identifier or path of the resource on the disk.
     *
     * @return string
     */
//    public function getResourcePath(): string
//    {
//        return (string) $this->resourcePath;
//    }

    /**
     * Checks whether the resource is a file.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->get('type', false) === 'file';
    }

    /**
     * Checks whether the resource is a directory.
     *
     * @return bool
     */
    public function isDir(): bool
    {
        return $this->get('type', false) === 'dir';
    }

    /**
     * Checks whether this resource is a public access or not
     *
     * @return bool
     */
    public function isPublish(): bool
    {
        return $this->has('public_key');
    }

    /**
     * Does exist this resource on the disk.
     *
     * @return bool
     */
    public function isExists(): bool
    {
        try
        {
            return (bool) ($this->getEntity() || $this->getRawContents());
        }
        catch (Exception $exc) {}

        return false;
    }

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
     * Getting and returns the current entity. If needed it will be refresh from api.
     *
     * @return Entity
     */
    abstract public function getEntity();

    /**
     * @return array Send a request to the API and return all the received data how it is.
     */
    abstract protected function getRawContents(): array;

}
