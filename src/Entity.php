<?php


namespace Arhitector\Yandex;

use Closure;

/**
 * The basic entity.
 *
 * @package Arhitector\Yandex
 */
class Entity
{

    /**
     * @var string[] The objects references map.
     */
    protected $objectMap = [];

    /**
     * @var array
     */
    protected $elements = [];

    /**
     * The Entity contains the elements of the array to access as model.
     *
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $this->convert($elements);
    }

    /**
     * @return int Current revision of the Drive.
     */
    public function getRevision(): int
    {
        return $this->get('revision');
    }

    /**
     * Check if there is a value with this key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->elements);
    }

    /**
     * Returns the value from the container by key. If `$default` is callable than the result will be returned.
     *
     * @param string $index
     * @param mixed  $default The default value.
     *
     * @return mixed
     */
    public function get($index, $default = null)
    {
        if ( ! $this->has($index))
        {
            return $default instanceof Closure ? $default($this) : $default;
        }

        return $this->elements[$index];
    }

    /**
     * Returns all elements as array.
     *
     * @param string[] $allowed Returns only this elements.
     *
     * @return array
     */
    public function toArray(array $allowed = null)
    {
        $contents = $this->elements;

        if ($allowed !== null)
        {
            $contents = array_intersect_key($this->elements, array_flip($allowed));
        }

        /*foreach ($contents as $index => $value)
        {
            if ($value instanceof Entity || method_exists($value, 'toArray'))
            {
                $contents[$index] = $value->toArray();
            }
        }*/

        return $contents; // вложенные тоже развернуть как массив key1, key2.key2-1, key3.key3-1
    }

    /**
     * Returns all elements as object.
     *
     * @param string[] $allowed Returns only this elements.
     *
     * @return object
     */
    public function toObject(array $allowed = null)
    {
        return (object) $this->toArray($allowed);
    }

    /**
     * @param array $elements
     *
     * @return array
     */
    protected function convert(array $elements): array
    {
        foreach ($elements as $index => $element)
        {
            if (isset($this->objectMap[$index]))
            {
                $elements[$index] = new $this->objectMap[$index]($element ?: []);
            }

//            if (is_array($data[$index]))
//            {
//                foreach ((array)$data[$index] as $k2 => $v) {
//                    $data[$index][$k2] = new static::$objectMap[$index]($v);
//                }
           // } else {
                //$elements[$index] = new $this->objectMap[$index]($element ?: []);
          //  }
        }

        return $elements;
    }

}
