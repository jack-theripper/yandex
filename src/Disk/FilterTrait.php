<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Disk
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Disk;

/**
 * Опции, доступные при получении списка ресурсов.
 *
 * @package Arhitector\Yandex\Disk
 */
trait FilterTrait
{

	/**
	 * @var array   параметры в запросе
	 */
	protected $parameters = [];

	/**
	 *	@var    bool   были внесены изменения в параметры
	 */
	protected $isModified = false;


	/**
	 * Количество ресурсов, вложенных в папку, описание которых следует вернуть в ответе
	 *
	 * @param    integer $limit
	 * @param    integer $offset установить смещение
	 *
	 * @return   $this
	 */
	public function setLimit($limit, $offset = null)
	{
		if (filter_var($limit, FILTER_VALIDATE_INT) === false)
		{
			throw new \InvalidArgumentException('Параметр "limit" должен быть целым числом.');
		}

		$this->isModified = true;
		$this->parameters['limit'] = (int) $limit;

		if ($offset !== null)
		{
			$this->setOffset($offset);
		}

		return $this;
	}

	/**
	 * Количество вложенных ресурсов с начала списка, которые следует опустить в ответе
	 *
	 * @param    integer $offset
	 *
	 * @return    $this
	 */
	public function setOffset($offset)
	{
		if (filter_var($offset, FILTER_VALIDATE_INT) === false)
		{
			throw new \InvalidArgumentException('Параметр "offset" должен быть целым числом.');
		}

		$this->isModified = true;
		$this->parameters['offset'] = (int) $offset;

		return $this;
	}

	/**
	 * Обрезать превью согласно размеру
	 *
	 * @param    boolean $crop
	 *
	 * @return    $this
	 */
	public function setPreviewCrop($crop)
	{
		$this->isModified = true;
		$this->parameters['preview_crop'] = (bool) $crop;

		return $this;
	}

	/**
	 * Размер уменьшенного превью файла
	 *
	 * @param    mixed $preview S, M, L, XL, XXL, XXXL, <ширина>, <ширина>x, x<высота>, <ширина>x<высота>
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setPreview($preview)
	{
		if (is_scalar($preview))
		{
			$preview = strtoupper($preview);
			$previewNum = str_replace('X', '', $preview, $replaces);

			if (in_array($preview, ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']) || (is_numeric($previewNum) && $replaces < 2))
			{
				if (is_numeric($previewNum))
				{
					$preview = strtolower($preview);
				}

				$this->isModified = true;
				$this->parameters['preview_size'] = $preview;

				return $this;
			}
		}

		throw new \UnexpectedValueException('Допустимые значения размера превью - S, M, L, XL, XXL, XXXL, <ширина>, <ширина>x, x<высота>, <ширина>x<высота>');
	}

	/**
	 * Атрибут, по которому сортируется список ресурсов, вложенных в папку.
	 *
	 * @param    string  $sort
	 * @param    boolean $inverse TRUE чтобы сортировать в обратном порядке
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setSort($sort, $inverse = false)
	{
		$sort = (string) $sort;

		if ( ! in_array($sort, ['name', 'path', 'created', 'modified', 'size', 'deleted']))
		{
			throw new \UnexpectedValueException('Допустимые значения сортировки - name, path, created, modified, size');
		}

		if ($inverse)
		{
			$sort = '-'.$sort;
		}

		$this->isModified = true;
		$this->parameters['sort'] = $sort;

		return $this;
	}

	/**
	 * Возвращает все параметры
	 *
	 * @return array
	 */
	public function getParameters(array $allowed = null)
	{
		if ($allowed !== null)
		{
			return array_intersect_key($this->parameters, array_flip($allowed));
		}

		return $this->parameters;
	}

	/**
	 *	Контейнер изменил состояние
	 *
	 *	@return  bool
	 */
	protected function isModified()
	{
		return (bool) $this->isModified;
	}

}