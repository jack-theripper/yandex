<?php
/**
 *	Контейнер данных
 *
 *	@package    Mackey\DataContainer
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\DataContainer;

/**
 *	Контейнер данных
 *
 *	@package	Mackey
 *	@subpackage	DataContainer
 */
trait ContainerTrait
{
	/**
	 *	@var	array  Контейнер данных
	 */
	protected $data = array();

	/**
	 *	@var    bool   когда данные из контейнера только для чтения
	 */
	protected $readOnly = false;
	
	/**
	 *	@var    bool   когда данные в контейнере были изменены
	 */
	protected $isModified = false;

	/**
	 *	Когда данные из контейнера только для чтения
	 *
	 *	@param   boolean	$readOnly  только для чтения
	 *	@return  $this
	 */
	public function setReadOnly($readOnly = true)
	{
		$this->readOnly = (bool) $readOnly;

		return $this;
	}

	/**
	 *	Проверить, контейнер для чтения или нет
	 *
	 *	@return  boolean  $readOnly	только для чтения
	 */
	public function isReadOnly()
	{
		return $this->readOnly;
	}
	
	/**
	 *	Контейнер изменил состояние
	 *
	 *	@return  bool
	 */
	public function isModified()
	{
		return (bool) $this->isModified;
	}

	/**
	 *	Проверить есть такой ключ в контейнере
	 *
	 *	@param   string  $key
	 *	@return  bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->getContents());
	}
	
	/**
	 *	Обновить данные по ключу
	 *
	 *	@param   string  $key
	 *	@param   mixed   $value
	 *	@return	$this
	 *	@throws	\RuntimeException
	 */
	public function set($key, $value)
	{
		if ($this->readOnly)
		{
			throw new \RuntimeException('Эти данные только для чтения.');
		}

		$this->isModified = true;

		if ($key === null)
		{
			$this->data[] = $value;

			return $this;
		}
		
		$this->data[(string) $key] = $value;

		return $this;
	}
	
	/**
	 *	Получить значение из контейнера по ключу
	 *
	 *	@param   string  $key		может быть NULL чтобы добавить в конец с числовым ключем
	 *	@param   mixed   $default	может быть функцией
	 *	@return  mixed
	 */
	public function get($key, $default = null)
	{
		if ($this->has($key))
		{
			return $this->data[$key];
		}

		if ($default instanceof \Closure)
		{
			return $default($this);
		}

		return $default;
	}
	
	/**
	 *	Заменить все данные контейнера другими.
	 *
	 *	@param   array  $data  новые данные
	 *	@return  $this
	 *	@throws  \RuntimeException
	 */
	public function setContents(array $data)
	{
		if ($this->readOnly)
		{
			throw new \RuntimeException('Эти данные только для чтения.');
		}

		$this->data = $data;
		$this->isModified = true;

		return $this;
	}

	/**
	 *	Получить данные контейнера
	 *
	 *	@return  array  контейнер
	 */
	public function getContents()
	{
		return $this->data;
	}
	
	/**
	 *	Удалить данные
	 *
	 *	@param	string	$key	ключ для удаления
	 *	@return void
	 *	@throws \RuntimeException
	 */
	public function delete($key)
	{
		if ($this->readOnly)
		{
			throw new \RuntimeException('Эти данные только для чтения');
		}

		$this->isModified = true;

		unset($this->data[$key]);
	}
	
	/**
	 *	IteratorAggregate
	 *
	 *	@return IteratorAggregate	итератор
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->getContents());
	}
	
	/**
	 *	Countable
	 *
	 *	@return	integer	размер контейнера
	 */
	public function count()
	{
		return count($this->getContents());
	}
	
	/**
	 *	isset магический метод
	 */
	public function __isset($key)
	{
		return $this->has($key);
	}

	/**
	 *	get магический метод
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 *	set	магический метод
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	
	/**
	 *	unset	магический метод
	 */
	public function __unset($key)
	{
		$this->delete($key);
	}
	
	/**
	 *	Разрешает использование isset()
	 *
	 *	@param	string	$key
	 *	@return	bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}
	
	/**
	 *	Разрешает доступ к ключам как к массиву
	 *
	 *	@param	string	$key
	 *	@return	mixed
	 *	@throws	\OutOfBoundsException
	 */
	public function offsetGet($key)
	{
		return $this->get($key, function() use ($key) {
			throw new \OutOfBoundsException('Индекс не существует '.$key);
		});
	}

	/**
	 *	Разрешает обновление свойств объекта как массива
	 *
	 *	@param	string	$key
	 *	@param	mixed	$value
	 */
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 *	Разрешает использование unset()
	 *
	 *	@param	string	$key
	 *	@throws	RuntimeException
	 */
	public function offsetUnset($key)
	{
		return $this->delete($key);
	}
}