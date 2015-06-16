<?php
/**
 *	Часть библиотеки по работе с Yandex REST API
 *
 *	@package    Mackey\Yandex
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
class Request extends \Curl\Curl
{
	/**
	 *	@var	string	поддержка заголовка hal+json
	 */
	private $json_pattern = '~^application/(?:(hal\+)?json|vnd\.api\+json)~i';
	
	/**
	 *	@var	array
	 */
	private $exceptions = [
		401 => 'UnauthorizedException',
		403 => 'ForbiddenException',
		404 => 'NotFoundException'
	];
	
	/**
	 *	@var	array
	 */
	protected $options = array();
	
	/**
	 *	Конструктор
	 *
	 *	@param	string	$base_url
	 */
	public function __construct($base_url = null)
	{
		parent::__construct($base_url);

		$this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$this->setJsonDecoder(function($response) {
			if (($json_obj = json_decode($response, true)) !== null)
			{
               return $json_obj;
            }

            return $response;
        });
	}
	
	/**
	 *	PATCH запрос
	 *
	 *	@param	mixed	$url
	 *	@param	mixed	$data
	 *	@return	mixed
	 */
    public function patch($url, $data = array())
    {
		if (is_array($url))
		{
            $data = $url;
            $url = $this->base_url;
        }

        $this->setURL($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
		
        return $this->exec();
    }
	
	/**
	 *	Выполнить запрос
	 *
	 *	@param	mixed	$ch
	 */
	public function exec($ch = null)
    {
		if ($this->headers)
		{
			$headers = array_filter($this->headers, 'strlen');
			$this->setOpt(CURLOPT_HTTPHEADER, array_map(function($value, $key) {
				return $key . ': ' . $value;
			}, $headers, array_keys($headers)));
		}
		
		$response = parent::exec($ch);

		// TODO совместимость с другими API
		if ($this->http_status_code && isset($this->exceptions[$this->http_status_code]))
		{
			$response['error'] = true;
			$response['message'] = implode('; ', $this->response);
		}

		if (isset($response['error']))
		{
			$exception = 'Exception';
			
			if (isset($this->exceptions[$this->error_code]))
			{
				$exception = 'Mackey\Yandex\Exception\\'.$this->exceptions[$this->error_code];
			}

			try
			{
				throw (new \ReflectionClass($exception))
					->newInstance($response['message'], $this->error_code);
			}
			catch (\ReflectionException $exc)
			{
				throw new \Exception($response['message'], $this->error_code, $exc);
			}
		}

		return $response;
	}
	
	/**
	 *	Разобрать URL
	 *
	 *	@param	string	$parse_url	URL
	 *	@param	string	$key
	 *	@return	mixed
	 */
	public function parse($parse_url, $key = null)
	{
		if ( ! is_string($parse_url))
		{
			throw new \InvalidArgumentException('Ожидается строка.');
		}
		
		$parse_url = parse_url($parse_url, PHP_URL_QUERY);
		
		if (is_string($parse_url))
		{
			parse_str($parse_url, $parse_url);
			
			if ($key !== null && isset($parse_url[$key]))
			{
				return $parse_url[$key];
			}
			
			return null;
		}
		
		return false;
	}

	/**
     *	Set Header. Автор Curl\Curl хернёй занимается
     *
     *	@access public
     *	@param  $key
     *	@param  $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;

		return $this;
    }
	
	/**
     *	Set Opt
     *
     *	@access public
     *	@param  $option
     *	@param  $value
     *
     *	@return boolean
     */
    public function setOpt($option, $value = null)
    {
        if (is_array($option))
		{
			return array_walk($option, function ($val, $key) {
				parent::setOpt($key, $val);
			});
		}
		
		return parent::setOpt($option, $value);
    }
	
	/**
	 *	Установить набор исключений на типовые события
	 *
	 */
	public function exceptions(array $exceptions = array())
	{
		$this->exceptions = $exceptions;
		
		return $this;
	}
	
}