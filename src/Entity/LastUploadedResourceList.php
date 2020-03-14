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
 * List of nested resources.
 *
 * @package Arhitector\Yandex\Entity
 */
class LastUploadedResourceList extends Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [
        'items' => Resource::class
    ];

    /**
     * @return int Number of elements per page
     */
    public function getLimit(): int
    {
        return $this->get('limit');
    }

}
