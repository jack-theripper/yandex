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
 * Comment identifiers.
 *
 * @package Arhitector\Yandex\Entity
 */
class CommentIds extends Entity
{

    /**
     * @return string The ID of the comments for private resources
     */
    public function getPrivateResource(): string
    {
        return $this->get('private_resource');
    }

    /**
     * @return string The ID of the comments for public resources
     */
    public function getPublicResource(): string
    {
        return $this->get('public_resource');
    }

}
