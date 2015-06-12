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
	 *	@var	string	Алгоритм шифрования
	 */
	protected $algorithm = MCRYPT_TWOFISH;
	
	/**
	 *	@var	string	Фраза
	 */
	protected $passphrase = null;
	
	/**
	 *	@var	resource	Mcrypt
	 */
	protected $ciper = null;
	
	/**
	 *	Включает отключает шифрование
	 *
	 *	@param	mixed	$encryption	NULL или boolean
	 *	@return	mixed
	 */
	public function encryption($encryption = null)
	{
		if ($encryption === null)
		{
			return $this->encryption;
		}
		
		$this->ciper = null;
		
		if (($this->encryption = (bool) $encryption))
		{
			if (is_string($encryption))
			{
				$this->phrase($encryption);
			}
			
			$this->ciper = mcrypt_module_open($this->algorithm, '', MCRYPT_MODE_CBC, '');
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
			return $this->passphrase;
		}

		$this->passphrase = (string) $phrase;

		return $this;
	}
	
	/**
	 *	Получить используемый шифровальщик
	 *
	 *	@return	mixed
	 */
	public function getCiper()
	{
		return $this->ciper;
	}
	
	/**
	 *	Получить IV
	 *
	 *	@return	string|false
	 */
	public function getEncryptionIV()
	{
		if ($this->ciper)
		{
			return substr(
				str_repeat(md5('iv'.$this->phrase(), 1), max(mcrypt_enc_get_supported_key_sizes($this->ciper)) / 16),
				0,
				mcrypt_enc_get_iv_size($this->ciper)
			);
		}
		
		return false;
	}
	
	/**
	 *	Получить key
	 *
	 *	@return	string|false
	 */
	public function getEncryptionKey()
	{
		if ($this->ciper)
		{
			return substr(
				str_repeat(md5('key'.$this->phrase(), 1), max(mcrypt_enc_get_supported_key_sizes($this->ciper)) / 16),
				0,
				mcrypt_enc_get_key_size($this->ciper)
			);
		}
		
		return false;
	}
	
	/**
	 *	Получить размер блока
	 *
	 *	@return	integer|false
	 */
	public function getEncryptionSize()
	{
		if ($this->ciper)
		{
			return mcrypt_enc_get_block_size($this->ciper);
		}
		
		return false;
	}
	
	/**
	 *	Деструктор
	 */
	public function __destruct()
	{
		if ($this->ciper)
		{
			mcrypt_module_close($this->ciper);
		}	
	}
	
	/**
	 *	Выполняет шифрование
	 *
	 *	@var	string	$data
	 *	@return	string
	 *	@throws	\InvalidArgumentException
	 */
	public function encrypt($data)
	{
		if ( ! is_string($data) or empty($data))
		{
			throw \InvalidArgumentException('Ожидается не пустая строка.');
		}

		mcrypt_generic_init($this->ciper, $this->getEncryptionKey(), $this->getEncryptionIV());
		$data = mcrypt_generic($this->ciper, $data);
        mcrypt_generic_deinit($this->ciper);

		return $data;
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
			throw \InvalidArgumentException('Ожидается не пустая строка.');
		}

		mcrypt_generic_init($this->ciper, $this->getEncryptionKey(), $this->getEncryptionIV());
		$data = mdecrypt_generic($this->ciper, $data);
        mcrypt_generic_deinit($this->ciper);

		return $data;
	}
	
	public function hasEncrypted()
	{
		try
		{
			$this->get('custom_properties', []);
			
			return 'encrypted' == true;
		}
		catch (\Exception $exc)
		{
			return false;
		}
	}
}