<?php

namespace Arhitector\Yandex\Entity;

use Arhitector\Yandex\Entity;

/**
 * Represents the user's disk model.
 *
 * @package Arhitector\Yandex\Entity
 */
class Disk extends Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [
        'user' => User::class,
        'system_folders' => SystemFolders::class
    ];

    /**
     * @return bool Sign that unlimited autoloading is enabled from mobile devices
     */
    public function isUnlimitedAutouploadEnabled(): bool
    {
        return $this->get('unlimited_autoupload_enabled');
    }

    /**
     * @return int Maximum supported file size
     */
    public function getMaxFileSize(): int
    {
        return $this->get('max_file_size');
    }

    /**
     * @return int Total disk size (bytes)
     */
    public function getTotalSpace(): int
    {
        return $this->get('total_space');
    }

    /**
     * @return int Total size of files in the trash (bytes). Included in `used_space`
     */
    public function getTrashSize(): int
    {
        return $this->get('trash_size');
    }

    /**
     * @return bool A sign of the presence of the purchased space
     */
    public function isPaid(): bool
    {
        return $this->get('is_paid');
    }

    /**
     * @return int Disk used size (bytes)
     */
    public function getUsedSpace(): int
    {
        return $this->get('used_space');
    }

    /**
     * @return SystemFolders Addresses of system folders in the user's disk
     */
    public function getSystemFolders(): SystemFolders
    {
        return $this->get('system_folders', function () {
            return new SystemFolders();
        });
    }

    /**
     * @return User The owner Of the disk.
     */
    public function getUser(): User
    {
        return $this->get('user', function () {
            return new User();
        });
    }

}
