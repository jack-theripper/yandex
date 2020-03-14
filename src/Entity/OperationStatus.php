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
 * The status of the asynchronous operation.
 *
 * @package Arhitector\Yandex\Entity
 */
class OperationStatus extends Entity
{

    /**
     * @return string The status of a operation
     */
    public function getStatus(): string
    {
        return $this->get('status');
    }

}
