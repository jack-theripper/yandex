<?php

namespace Arhitector\Yandex\Entity;

use Arhitector\Yandex\Entity;

/**
 * Public file or folder.
 *
 * @package Arhitector\Yandex\Entity
 */
class PublicResource extends Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [
        'share' => ShareInfo::class
    ];

    /**
     * @return string The verification status of antivirus
     */
    public function getAntivirusStatus(): string
    {
        return $this->get('antivirus_status');
    }

    /**
     * @return int Public resource view count
     */
    public function getViewsCount(): int
    {
        return $this->get('views_count');
    }

    /**
     * @return string Resource identifier
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
        return $this->get('share', function () {
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
     * @return UserPublicInformation Owner of a published resource
     */
    public function getOwner(): UserPublicInformation
    {
        return $this->get('owner', function () {
            return new UserPublicInformation();
        });
    }

    /*
PublicResource {
size (integer, optional): <Размер файла>,
photoslice_time (string, optional): <Дата создания фото или видео файла>,
_embedded (PublicResourceList, optional): <Список вложенных ресурсов>,
exif (Exif, optional): <Метаданные медиафайла (EXIF)>,
media_type (string, optional): <Определённый Диском тип файла>,
sha256 (string, optional): <SHA256-хэш>,
type (string): <Тип>,
mime_type (string, optional): <MIME-тип файла>,
revision (integer, optional): <Ревизия Диска в которой этот ресурс был изменён последний раз>,
public_url (string, optional): <Публичный URL>,
path (string): <Путь опубликованного ресурса>,
md5 (string, optional): <MD5-хэш>,
public_key (string): <Ключ опубликованного ресурса>,
preview (string, optional): <URL превью файла>,
name (string): <Имя>,
created (string): <Дата создания>,
modified (string): <Дата изменения>,
comment_ids (CommentIds, optional): <Идентификаторы комментариев>
}
*/

}
