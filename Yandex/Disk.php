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

use \Mackey\DataContainer\ContainerTrait;

/**
 *	Клиент для работы с Яндекс-диском
 *
 *	@package	Mackey
 *	@subpackage	Yandex
 */
class Disk extends Client implements \ArrayAccess, \IteratorAggregate
{
	use ContainerTrait {
		ContainerTrait::getContents as _getContents;
	}

	/**
     *	@var	string	скомпиированный адрес API диска
     */
    protected $api_request = 'https://cloud-api.yandex.net/v1/disk/';
	
	/**
	 *	@var	array	идентификаторы операций за сессию
	 */
	private $operations = array();

	/**
	 *	Конструктор
	 *
	 *	@param	mixed	$token	маркер доступа
	 *	@throws	\InvalidArgumentException
	 */
	public function __construct($token = null)
	{
		try
		{
			parent::__construct($token);
		}
		catch (\InvalidArgumentException $exc)
		{
			if ($token instanceof Client)
			{
				return $this->token($token->token());
			}

			throw $exc;		
		}
		finally
		{
			/**
			 *	@return	mixed
			 */
			$this->request->complete(function ($response = null) {
				if ($this->request->http_status_code == 202 && isset($response->response['href']))
				{
					if ( ! ($parse = $this->request->parse($response->response['href'], 'id')))
					{
						$parse = substr(strrchr($response->response['href'], '/'), 1);
					}
					
					if ( ! empty($parse))
					{
						$this->operations[] = $response->response['operation'] = $parse;
					}
				}

				return $response->response;
			});
		}
		
		$this->setReadOnly(true);
		$this->request->exceptions([
			400 => 'UnsupportedException',
			401 => 'UnauthorizedException',
			403 => 'ForbiddenException',
			404 => 'NotFoundException',
			409 => 'AlreadyExistsException',
			423 => 'ForbiddenException',
			406 => 'UnsupportedException',
			415 => 'UnsupportedException',
			507 => 'OutOfSpaceException'
		]);
	}
	
	/**
	 *	Получает информацию о диске
	 *
	 *	@return	array
	 */
	public function getContents()
	{
		if ( ! $this->_getContents())
		{
			$response = (array) $this->request->get($this->getRequestUrl(''));
			$this->setReadOnly(false)
				->setContents($response += ['free_space' => $response['total_space'] - $response['used_space']])
				->setReadOnly(true);
		}

		return $this->_getContents();
	}
	
	/**
	 *	Устанавливает либо получает уже установленный OAuth-токен.
	 *
	 *	@param	string	$token	передать NULL чтобы получить установленный маркер доступа
	 *	@return	mixed	если устанавливается маркер, вернет $this
	 */
	public function token($token = null)
	{
		if (is_string($token))
		{
			$this->request->setHeader('Authorization', 'OAuth '.$token);
			$this->request->setHeader('Content-Type', $this->contentType());
			//$this->request->setHeader('Accept', 'application/hal+json');
		}
		
		return parent::token($token);
	}		

	/**
	 *	Работа с ресурсами на диске
	 *
	 *	@param	string	$path	Путь к новому либо уже существующему ресурсу, NULL Список всех файлов
	 *	@param	integer	$limit
	 *	@param	integer	$offset
	 *	@return	mixed
	 */
	public function resource($path = null, $limit = 20, $offset = 0)
	{
		if ($path === null)
		{
			return (new Disk\ResourceList(function ($request_params) {
				$response = $this->request->get($this->getRequestUrl('resources/files', $request_params));
				
				if (isset($response['items']))
				{
					$response = array_map(function ($item) {
						return new Disk\Resource($item, $this, $this->request);
					}, $response['items']);
				}

				return $response;
			}))
			->limit($limit, $offset);
		}
		
		if ( ! is_string($path))
		{
			throw new \InvalidArgumentException('Передайте строку - путь к файлу или папке, либо NULL');
		}

		if (stripos($path, 'disk:/') === 0)
		{
			$path = substr($path, 6);
		}

		if(stripos($path, 'app:/') === 0){
			return new Disk\Resource($path, $this, $this->request);
		}

		return new Disk\Resource('disk:/'.ltrim($path, ' /'), $this, $this->request);
	}

	/**
	 *	Получить статус операции либо получить все операции, полученные во время выполнения сценария
	 *
	 *	@param	string	$identifier	идентификатор операции или NULL
	 *	@return	mixed	текстовое описание статуса, FALSE либо массив идентификаторов операции
	 */
	public function operation($identifier = null)
	{
		if ($identifier === null)
		{
			return $this->operations;
		}
		
		$response = $this->request->get($this->getRequestUrl('operations/'.$identifier));

		if (isset($response['status']))
		{
			return $response['status'];
		}
		
		return false;
	}
	
	/**
	 *	Работа с опубликованными ресурсами, получение списка опубликованных файлов и папок
	 *
	 *	@param	mixed	$public_key	Публичный ключ к опубликованному ресрсу или NULL для получения списка ресурсов
	 *	@return	Disk\Publish
	 */
	public function publish($public_key = null, $limit = 20, $offset = 0)
	{
		if ( ! is_numeric($limit) or ! is_numeric($offset))
		{
			throw new \InvalidArgumentException('Параметры "limit" и "offset" должны быть числом.');
		}
		
		$this->required_token = false;

		if ($public_key === null)
		{
			return (new Disk\ResourceList(function ($request_params) {
				$response = $this->request->get($this->getRequestUrl('resources/public', $request_params));
				
				if (isset($response['items']))
				{
					return array_map(function ($item) {
						return new Disk\ResourcePublish($item, $this, $this->request);
					}, $response['items']);
				}

				return [];
			}))
			->limit($limit, $offset);
		}
		
		return (new Disk\ResourcePublish($public_key, $this, $this->request))
			->limit($limit, $offset);
	}
	
	/**
	 *	Очистка Корзины\Удаление файла из корзины
	 *
	 *	@param	string	путь к файлу в корзине
	 *	@param	boolean
	 */
	public function trash($parameter = null, $limit = 20, $offset = 0)
	{
		if ($parameter === null or (is_string($parameter) && ! strlen($parameter)))
		{
			return (new Disk\ResourceList(function (array $request_params) {
				if ( ! empty($request_params['sort']) && ! in_array($request_params['sort'], ['deleted', 'created', '-deleted', '-created']))
				{
					throw new \UnexpectedValueException('Допустимые значения сортировки - deleted, created и в обратном порядке.');
				}
				
				$response = $this->request->get($this->getRequestUrl('trash/resources', $request_params + ['path' => 'trash:/']));
				
				if (isset($response['_embedded']['items']))
				{
					return array_map(function ($item) {
						return new Disk\ResourceTrash($item, $this, $this->request);
					}, $response['_embedded']['items']);
				}
				
				return [];
			}))
			->sorting('created')
			->limit($limit, $offset);
		}
		
		if ($parameter === true)
		{
			$response = $this->request->delete($this->getRequestUrl('trash/resources'));
			
			if ( ! empty($response['operation']))
			{
				return $response['operation'];
			}
		
			return $this->request->http_status_code == 204;
		}
		
		if ( ! is_string($parameter))
		{
			throw new \InvalidArgumentException('Передайте строку - путь к файлу или папке, либо NULL.');
		}

		if (stripos($parameter, 'trash:/') === 0)
		{
			$parameter = substr($parameter, 7);
		}

		return new Disk\ResourceTrash('trash:/'.ltrim($parameter, ' /'), $this, $this->request);		
	}

	/**
	 *	Последние загруженные файлы
	 *
	 *	@param	integer	$limit
	 *	@param	integer	$offset
	 *	@return	array
	 */
	public function uploaded($limit = 20, $offset = 0)
	{
		if ( ! is_numeric($limit) or ! is_numeric($offset))
		{
			throw new \InvalidArgumentException('Параметры "limit" и "offset" должны быть числом.');
		}
		
		return (new Disk\ResourceList(function ($request_params) {
			$response = $this->request->get($this->getRequestUrl('resources/last-uploaded', $request_params));
			
			if (isset($response['items']))
			{
				$response = array_map(function ($item) {
					return new Disk\Resource($item, $this, $this->request);
				}, $response['items']);
			}

			return $response;
		}))
		->limit($limit, $offset);
	}
	
}