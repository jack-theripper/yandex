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
 * Contents of the trash.
 *
 * @package Arhitector\Yandex\Entity
 */
class TrashResource extends Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [
        'share'       => ShareInfo::class,
        '_embedded'   => TrashResourceList::class,
        'exif'        => Exif::class,
        'comment_ids' => CommentIds::class
    ];

    /**
     * @return string Статус проверки антивирусом
     */
    public function getAntivirusStatus(): string
    {
        return $this->get('antivirus_status');
    }

    /**
     * @return string Идентификатор ресурса
     */
    public function getResourceId(): string
    {
        return $this->get('resource_id');
    }

    /**
     * @return ShareInfo Информация об общей папке
     */
    public function getShare(): ShareInfo
    {
        return $this->get('share');
    }

    /**
     * @return string URL для скачивания файла
     */
    public function getFile(): string
    {
        return $this->get('file');
    }

    /**
     * @return int Размер файла
     */
    public function getSize(): int
    {
        return $this->get('size');
    }

    /**
     * @return string Дата создания фото или видео файла
     */
    public function getPhotosliceTime(): string
    {
        return $this->get('photoslice_time');
    }

    /**
     * @return TrashResourceList Список вложенных ресурсов
     */
    public function getEmbedded(): TrashResourceList
    {
        return $this->get('_embedded');
    }

    /**
     * @return Exif Метаданные медиафайла (EXIF)
     */
    public function getExif(): Exif
    {
        return $this->get('exif');
    }

    /**
     * @return object Пользовательские атрибуты ресурса
     */
    public function getCustomProperties(): object
    {
        return $this->get('custom_properties');
    }

    /**
     * @return string Путь откуда был удалён ресурс
     */
    public function getOriginPath(): string
    {
        return $this->get('origin_path');
    }

    /**
     * @return string Определённый Диском тип файла
     */
    public function getMediaType(): string
    {
        return $this->get('media_type');
    }

    /**
     * @return string SHA256-хэш
     */
    public function getSha256(): string
    {
        return $this->get('sha256');
    }

    /**
     * @return string Тип
     */
    public function getType(): string
    {
        return $this->get('type');
    }

    /**
     * @return string MIME-тип файла
     */
    public function getMimeType(): string
    {
        return $this->get('mime_type');
    }

    /**
     * @return int Ревизия Диска в которой этот ресурс был изменён последний раз
     */
    public function getRevision(): int
    {
        return $this->get('revision');
    }

    /**
     * @return string Дата добавления в корзину(для ресурсов в корзине)
     */
    public function getDeleted(): string
    {
        return $this->get('deleted');
    }

    /**
     * @return string Публичный URL
     */
    public function getPublicUrl(): string
    {
        return $this->get('public_url');
    }

    /**
     * @return string Путь к ресурсу
     */
    public function getPath(): string
    {
        return $this->get('path');
    }

    /**
     * @return string MD5-хэш
     */
    public function getMd5(): string
    {
        return $this->get('md5');
    }

    /**
     * @return string Ключ опубликованного ресурса
     */
    public function getPublicKey(): string
    {
        return $this->get('public_key');
    }

    /**
     * @return string URL превью файла
     */
    public function getPreview(): string
    {
        return $this->get('preview');
    }

    /**
     * @return string Имя
     */
    public function getName(): string
    {
        return $this->get('name');
    }

    /**
     * @return string Дата создания
     */
    public function getCreated(): string
    {
        return $this->get('created');
    }

    /**
     * @return string Дата изменения
     */
    public function getModified(): string
    {
        return $this->get('modified');
    }

    /**
     * @return CommentIds Идентификаторы комментариев
     */
    public function getCommentIds(): CommentIds
    {
        return $this->get('comment_ids');
    }

}
