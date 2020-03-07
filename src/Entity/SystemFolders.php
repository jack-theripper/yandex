<?php

namespace Arhitector\Yandex\Entity;

use Arhitector\Yandex\Entity;

/**
 * Addresses of system folders in the user's Disk.
 *
 * @package Arhitector\Yandex\Entity
 */
class SystemFolders extends Entity
{

    /**
     * @return string Path to the downloads folder
     */
    public function getDownloads(): string
    {
        return $this->get('downloads');
    }

    /**
     * @return string Path to the applications folder
     */
    public function getApplications(): string
    {
        return $this->get('applications');
    }

}
