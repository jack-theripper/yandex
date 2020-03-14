<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
        return $this->get('is_root', false);
    }

    /**
     * @return bool Indicates that the current user is the owner of the shared folder
     */
    public function isOwned(): bool
    {
        return $this->get('is_owned', false);
    }

    /**
     * @return string access permission
     */
    public function getPermissions(): string
    {
        return $this->get('rights');
    }

}
