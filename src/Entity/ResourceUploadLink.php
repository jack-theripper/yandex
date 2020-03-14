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
 * Link to upload the file.
 *
 * @package Arhitector\Yandex\Entity
 */
class ResourceUploadLink extends Entity
{

    /**
     * @return string ID of the file upload operation
     */
    public function getOperationId(): string
    {
        return $this->get('operation_id');
    }

    /**
     * @return string URL
     */
    public function getHref(): string
    {
        return $this->get('href');
    }

    /**
     * @return string HTTP method
     */
    public function getMethod(): string
    {
        return $this->get('method');
    }

    /**
     * @return bool Flag for a templated URL
     */
    public function getTemplated(): bool
    {
        return $this->get('templated');
    }

}
