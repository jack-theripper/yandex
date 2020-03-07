<?php

namespace Arhitector\Yandex\Entity;

use Arhitector\Yandex\Entity;

/**
 * Information about the shared folder.
 *
 * @package Arhitector\Yandex\Entity
 */
class ShareInfo extends Entity
{

    /**
     * @return bool Indicates that the folder is the root folder in the group
     */
    public function isRoot(): bool
    {
        return $this->get('is_root');
    }

    /**
     * @return bool Indicates that the current user is the owner of the shared folder
     */
    public function isOwned(): bool
    {
        return $this->get('is_owned');
    }

    /**
     * @return string access permission
     */
    public function getPermissions(): string
    {
        return $this->get('rights');
    }

}
