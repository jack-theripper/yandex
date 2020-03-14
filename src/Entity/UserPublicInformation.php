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
 * Owner of a published resource.
 *
 * @package Arhitector\Yandex\Entity
 */
class UserPublicInformation extends Entity
{

    /**
     * @return string Login
     */
    public function getLogin(): string
    {
        return $this->get('login');
    }

    /**
     * @return string Display name of the user
     */
    public function getDisplayName(): string
    {
        return $this->get('display_name');
    }

    /**
     * @return string User identifier
     */
    public function getUid(): string
    {
        return $this->get('uid');
    }

}
