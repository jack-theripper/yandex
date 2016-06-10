<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Disk\Filter
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Disk\Filter;

/**
 * Фильтр для превью.
 * Использует возможности Яндес.Диска.
 *
 * @package Arhitector\Yandex\Disk\Filter
 */
trait PreviewTrait
{

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
	 * Получает установленное значение "setPreview".
	 *
	 * @return  string
	 */
	public function getPreview()
	{
		if (isset($this->parameters['preview_size']))
		{
			return $this->parameters['preview_size'];
		}

		return null;
	}

	/**
	 * Получает установленное значение "setPreviewCrop".
	 *
	 * @return  string
	 */
	public function getPreviewCrop()
	{
		if (isset($this->parameters['preview_crop']))
		{
			return $this->parameters['preview_crop'];
		}

		return null;
	}

}