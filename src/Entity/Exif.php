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
 * Media file metadata (EXIF).
 *
 * @package Arhitector\Yandex\Entity
 */
class Exif extends Entity
{

    /**
     * @return string Shooting date
     */
    public function getDateTime(): string
    {
        return $this->get('date_time');
    }

}
