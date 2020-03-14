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
 * Represents a specific disk user.
 *
 * @package Arhitector\Yandex\Entity
 */
class User extends Entity
{

    /**
     * @return string The country
     */
    public function getCountry(): string
    {
        return $this->get('country');
    }

    /**
     * @return string The login
     */
    public function getLogin(): string
    {
        return $this->get('login');
    }

    /**
     * @return string Display name
     */
    public function getDisplayName(): string
    {
        return $this->get('display_name');
    }

    /**
     * @return string The user ID
     */
    public function getUid(): string
    {
        return $this->get('uid');
    }

}
