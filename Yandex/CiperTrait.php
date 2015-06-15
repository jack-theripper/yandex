<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey
 *	@version    1.0
 *	@author     Arhitector
 *	@license    MIT License
 *	@copyright  2015 Arhitector
 *	@link       http://pruffick.ru
 */
namespace Mackey\Yandex;

/**
 *	Интерфейс для работе с ресурсом
 *
 *	@package	Yandex
 */
trait CiperTrait
{

	/**
	 *	@var	boolean	Шифрование включено
	 */
	protected $encryption = false;
	
	/**
	 *	@var	string	Фраза
	 */
	protected $passphrase;
	
	/**
	 *	@var	string	Вектор
	 */
	protected $iv;
	
	/**
	 *	@var	string	Длина
	 */
	protected $ivSize;

	/**
	 *	@var	string	Алгоритм шифрования
	 */
	protected $cipher = 'AES-256-OFB';
	
	/**
	 *	Включает отключает шифрование
	 *
	 *	@param	mixed	$encryption	NULL или boolean или string пароль
	 *	@return	mixed
	 */
	public function encryption($encryption = null)
	{
		if ($encryption === null)
		{
			return $this->encryption;
		}

		if (($this->encryption = (bool) $encryption))
		{
			if ( ! extension_loaded('openssl'))
			{
				throw new \RuntimeException('Для поддержки шифрования нужно PHP расширение OpenSSL.');
			}
			
			if (is_string($encryption))
			{
				$this->phrase($encryption);
			}

			$this->ivSize = openssl_cipher_iv_length($this->cipher);
		}

		return $this;
	}

	/**
	 *	Секретная фраза
	 *
	 *	@var	$passphrase	mixed	Фраза или NULL чтобы получить текущее значение
	 *	@return	mixed
	 */
	public function phrase($phrase = null)
	{
		if ($phrase === null)
		{
			return $this->passphrase ?: $this->cipher;
		}

		$this->passphrase = (string) $phrase;

		return $this;
	}

	/**
	 *	Получить новый вектор инициализации
	 *
	 *	@param	$property	mixed	NULL получить текущий вектор, TRUE установить случайный, FALSE получить и стереть вектор, или строка
	 *	@return	
	 */
	public function vector($property = null)
	{
		if ($property === null)
		{
			return $this->iv;
		}
		else if ($property === true)
		{
			return $this->iv = openssl_random_pseudo_bytes($this->ivSize);
		}
		else if ($property === false)
		{
			$vector = $this->iv;
			$this->iv = null;
			
			return $vector;
		}
		
		$this->iv = (string) $property;
		
		return $this;
	}

	/**
	 *	Выполняет шифрование
	 *
	 *	@var	string	$data
	 *	@return	string
	 *	@throws	\InvalidArgumentException
	 */
	public function encrypt($data, $raw = true)
	{
		// TODO	 serialize($data)
		if ( ! is_string($data) or empty($data))
		{
			throw new \InvalidArgumentException('Ожидается не пустая строка.');
		}

		return openssl_encrypt($data, $this->cipher, $this->phrase() ?: $this->cipher, OPENSSL_RAW_DATA, $this->vector() ?: $this->vector(true));
	}

	/**
	 *	Выполняет расшифровывание
	 *
	 *	@var	string	$data	зашифрованная строка
	 *	@return	string
	 *	@throws	\InvalidArgumentException
	 */
	public function decrypt($data)
	{
		if ( ! is_string($data) or empty($data))
		{
			throw new \InvalidArgumentException('Ожидается не пустая строка.');
		}
		
		if ( ! $this->vector())
		{
			throw new \RuntimeException('Не установлен вектор инициализации, который был использован при кодировании.');
		}

		// TODO	unserialize()
		return openssl_decrypt($data, $this->cipher, $this->phrase() ?: $this->cipher, OPENSSL_RAW_DATA, $this->vector());
	}
	
	/**
	 *	Проверяет этот файл зашифрован или нет
	 *
	 */
	public function hasEncrypted()
	{
		try
		{
			return ! empty($this->get('custom_properties', ['ecnrypted' => false])['encrypted']);
		}
		catch (\Exception $exc)
		{
			return false;
		}
	}

	/**
     *	MAC хэш
     *
     *	@param  string  $iv
     *	@param  string  $value
     *	@return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->phrase());
    }
}