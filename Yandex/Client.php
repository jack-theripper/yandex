<?php
/**
 *	Часть библиотеки по работе с сервисами Яндекса
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
 *	Клиент для определения доспута к API
 *
 *	@package	Mackey
 *	@subpackage	Yandex
 */
class Client
{
	const OAUTH_API = 'https://oauth.yandex.ru/';

	/**
	 *	@var	Request	подготовленный экземпляр
	 */
	protected $request;

	/**
     *	@var	string	для обращения к API требуется маркер доступа
     */
    protected $required_token = true;

	/**
     *	@var	string	скомпилированный HTTP адрес к API
     */
    protected $api_request = 'https://oauth.yandex.ru/';
	
	/**
	 *	@var	string	формат обмена данными
	 */
	protected $content_type = 'application/json';

	/**
     *	@var	string	OAuth-токен
     */
    protected $token = null;
	
	/**
     *	@var string
     */
    private $client = '';
	
    /**
     *	@var string
     */
    private $clientSecret = '';
	
	/**
	 *	Конструктор
	 *
	 *	@param	string	$token	OAuth-токен
	 *	@throws	InvalidArgumentException
	 */
	public function __construct($token = null)
	{
		$this->request = new Request;

		/**
		 *	@throws	LogicException
		 */
		$this->request->beforeSend(function () {
			if ( ! $this->token() && $this->required_token)
			{
				throw new \LogicException('Требуется установить маркер доступа или OAuth-токен.');
			}
		});

		if ( ! empty($token) && ! is_string($token))
		{
			throw new \InvalidArgumentException('Такой маркер доступа или OAuth-токен не поддерживается.');
		}

		$this->token($token);
	}
	
	/**
	 *	Устанавливает либо получает уже установленный OAuth-токен.
	 *
	 *	@param	string	$token	передать NULL чтобы получить установленный маркер доступа
	 *	@return	mixed	если устанавливается маркер, вернет $this
	 */
	public function token($token = null)
	{
		if ($token === null)
		{
			return $this->token;
		}

		$this->token = (string) $token;

		return $this;
	}
	
	/**
	 *	Устанавливает или получает версию API
	 *
	 *	@param	string	$version	установить версию или NULL чтобы получить текушую
	 *	@return	mixed
	 */
	public function version($version = null)
	{
		// TODO
	}

	/**
	 *	Получить URL запроса к API
	 *
	 *	@param	string	$path_url
	 *	@param	array	$params
	 *	@return	string
	 */
	public function getRequestUrl($path_url, $params = array())
	{
		if (is_array($params) && ! empty($params))
		{
			$params = '?'.http_build_query(array_map('trim', $params));
		}
		
		if ( ! is_string($params))
		{
			$params = '';
		}

		return rtrim($this->api_request.$path_url, '?').$params;
	}
	
	/**
	 *	Формат обмена данными
	 *
	 *	@return	string
	 */
	public function contentType()
	{
		return $this->content_type;
	}
	
	public function setClient($client_id)
	{
		$this->client = (string) $client_id;
		
		return $this;
	}
	
	public function getClient()
	{
		return $this->client;
	}
	
	public function getClientSecret()
	{
		return $this->clientSecret;
	}
	
	public function setClientSecret($client_secret)
	{
		$this->clientSecret = (string) $client_secret;
		
		return $this;
	}
	
	public function requestToken($username, $password, $onlyToken = false)
	{
		try
		{
			$a = $this->required_token;
			$this->required_token = false;
			// RFC 6749
			$data = (array) $this->request->post($this->api_request.'/token', array(
				'grant_type'	=> 'password',
				'client_id'		=> $this->getClient(),
				'client_secret' => $this->getClientSecret(),
				'username'		=> (string) $username,
				'password'		=> (string) $password
			));
			$this->required_token = $a;
			
			if (isset($data['error']))
			{
				throw new Exception($data['error']);
			}
			
			if ($onlyToken)
			{
				return (string) $data['access_token'];
			}

			return $data + array(
				'token' 		=> $data['access_token'],
				'ttl'			=> (int) $data['expires_in'],
				'created_at'	=> time()
			);
		}
		catch (Exception $exc)
		{
			return false;
		}
	}
	
}