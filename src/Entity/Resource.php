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
 * The private resource
 *
 * @package Arhitector\Yandex\Entity
 */
class Resource extends Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [
        'share'       => ShareInfo::class,
        '_embedded'   => ResourceList::class,
        'exif'        => Exif::class,
        'comment_ids' => CommentIds::class
    ];

    /**
     * @return string The verification status of antivirus
     */
    public function getAntivirusStatus(): string
    {
        return $this->get('antivirus_status');
    }

    /**
     * @return string Public resource view count
     */
    public function getResourceId(): string
    {
        return $this->get('resource_id');
    }

    /**
     * @return ShareInfo Information about the shared folder
     */
    public function getShare(): ShareInfo
    {
        return $this->get('share', function() {
            return new ShareInfo();
        });
    }

    /**
     * @return string URL for downloading the file
     */
    public function getFile(): string
    {
        return $this->get('file');
    }

    /**
     * @return int File size
     */
    public function getSize()
    {
        return $this->get('size');
    }

    /**
     * @return string Date when the photo or video file was created
     */
    public function getPhotosliceTime(): string
    {
        return $this->get('photoslice_time');
    }

    /**
     * @return ResourceList List of nested resources
     */
    public function getEmbedded(): ResourceList
    {
        return $this->get('_embedded', function () {
            return new ResourceList();
        });
    }

    /**
     * @return Exif Media file metadata (EXIF)
     */
    public function getExif(): Exif
    {
        return $this->get('exif', function () {
            return new Exif();
        });
    }

    /**
     * @return object Custom attributes of resource
     */
    public function getCustomProperties(): object
    {
        return $this->get('custom_properties');
    }

    /**
     * @return string Media file type that was recognized by the disk
     */
    public function getMediaType(): string
    {
        return $this->get('media_type');
    }

    /**
     * @return string Url of the preview file
     */
    public function getPreview(): string
    {
        return $this->get('preview');
    }

    /**
     * @return string The type
     */
    public function getType(): string
    {
        return $this->get('type');
    }

    /**
     * @return string MIME file type
     */
    public function getMimeType(): string
    {
        return $this->get('mime_type');
    }

    /**
     * @return string Public URL
     */
    public function getPublicUrl(): string
    {
        return $this->get('public_url');
    }

    /**
     * @return string Path of the published resource
     */
    public function getPath(): string
    {
        return $this->get('path');
    }

    /**
     * @return string MD5-hash
     */
    public function getMd5(): string
    {
        return $this->get('md5');
    }

    /**
     * @return string Published resource key
     */
    public function getPublicKey(): string
    {
        return $this->get('public_key');
    }

    /**
     * @return string SHA256-hash
     */
    public function getSha256(): string
    {
        return $this->get('sha256');
    }

    /**
     * @return string The name
     */
    public function getName(): string
    {
        return $this->get('name');
    }

    /**
     * @return string Date of created
     */
    public function getCreated(): string
    {
        return $this->get('created');
    }

    /**
     * @return string Date of modified
     */
    public function getModified(): string
    {
        return $this->get('modified');
    }

    /**
     * @return CommentIds Comment IDs
     */
    public function getCommentIds(): CommentIds
    {
        return $this->get('comment_ids', function () {
            return new CommentIds();
        });
    }

}
