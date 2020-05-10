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

use Arhitector\Yandex\Disk\Resource\EntityTrait;
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
    use FilterTrait, EmitterTrait, EntityTrait;

    /**
     * @var DiskClient The client that spawned the resource.
     */
    protected $client;

    /**
     * @var string[]
     */
    protected $parametersAllowed = ['limit', 'offset', 'preview_crop', 'preview_size', 'sort'];

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
     * @return array Send a request to the API and return all the received data how it is.
     */
    abstract protected function getRawContents(): array;

}
