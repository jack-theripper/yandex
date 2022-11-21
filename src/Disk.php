<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */

namespace Arhitector\Yandex;

use Arhitector\Yandex\Client\Container\ContainerTrait;
use Arhitector\Yandex\Client\Exception\UnsupportedException;
use Arhitector\Yandex\Client\OAuth;
use League\Event\Emitter;
use League\Event\EmitterTrait;
use Psr\Http\Message\RequestInterface;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;

/**
 * Клиент для Яндекс.Диска
 *
 * @package Arhitector\Yandex
 */
class Disk extends OAuth implements \ArrayAccess, \IteratorAggregate, \Countable
{
	use ContainerTrait, EmitterTrait {
		toArray as protected _toArray;
	}

	/**
	 * @const   адрес API
	 */
	const API_BASEPATH = 'https://cloud-api.yandex.net/v1/disk/';

	/**
	 * @var    array   соответствие кодов ответа к типу исключения
	 */
	protected $exceptions = [

		/**
		 * Некорректные данные (Bad Request).
		 */
		400 => 'Arhitector\Yandex\Client\Exception\UnsupportedException',

		/**
		 * Не авторизован (Unauthorized).
		 */
		401 => 'Arhitector\Yandex\Client\Exception\UnauthorizedException',

		/**
		 * Доступ запрещён (Forbidden).
		 * Возможно, у приложения недостаточно прав для данного действия.
		 */
		403 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Не удалось найти запрошенный ресурс (Not Found).
		 */
		404 => 'Arhitector\Yandex\Client\Exception\NotFoundException',

		/**
		 * Ресурс не может быть представлен в запрошенном формате (Not Acceptable).
		 */
		406 => 'Arhitector\Yandex\Disk\Exception\UnsupportedException',

		/**
		 * Конфликт путей/имён.
		 */
		409 => [

			/**
			 * Указанного пути не существует.
			 */
			'DiskPathDoesntExistsError' => 'Arhitector\Yandex\Client\Exception\NotFoundException',

			/**
			 * Ресурс уже существует
			 */
			'DiskResourceAlreadyExistsError' => 'Arhitector\Yandex\Disk\Exception\AlreadyExistsException',

			/**
			 * Уже существует папка с таким именем.
			 */
			'DiskPathPointsToExistentDirectoryError' => 'Arhitector\Yandex\Disk\Exception\AlreadyExistsException'
		],

		/**
		 * Ресурс не может быть представлен в запрошенном формате (Unsupported Media Type).
		 */
		415 => 'Arhitector\Yandex\Client\Exception\UnsupportedException',

		/**
		 * Ресурс заблокирован (Locked).
		 * Возможно, над ним выполняется другая операция.
		 */
		423 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Слишком много запросов(Too Many Requests).
		 */
		429 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Сервис временно недоступен(Service Unavailable).
		 */
		503 => 'Arhitector\Yandex\Client\Exception\ServiceException',

		/**
		 * Недостаточно свободного места (Insufficient Storage).
		 */
		507 => 'Arhitector\Yandex\Disk\Exception\OutOfSpaceException'
	];

	/**
	 * @var    array    идентификаторы операций за сессию
	 */
	protected $operations = [];

	/**
	 * @var string имя класса коллекции ресурсов
	 */
	protected $collectionClass = Disk\Resource\Collection::class;

	/**
	 * Конструктор
	 *
	 * @param    mixed $token маркер доступа
	 *
	 * @throws    \InvalidArgumentException
	 *
	 * @example
	 *
	 * new Disk('token')
	 * new Disk() -> setAccessToken('token')
	 * new Disk( new Client('token') )
	 */
	public function __construct($token = null)
	{
		$this->setEmitter(new Emitter);

		if ($token instanceof AbstractClient) {
			$token = $token->getAccessToken();
		}

		parent::__construct($token);
	}

	/**
	 * Получает информацию о диске
	 *
	 * @param array $allowed
	 *
	 * @return array
	 * @example
	 *
	 * array (size=5)
	 * 'trash_size' => int 9449304
	 * 'total_space' => float 33822867456
	 * 'used_space' => float 25863284099
	 * 'free_space' => float 7959583357
	 * 'system_folders' => array (size=2)
	 *      'applications' => string 'disk:/Приложения' (length=26)
	 *      'downloads' => string 'disk:/Загрузки/' (length=23)
	 */
	public function toArray(array $allowed = null)
	{
		if (!$this->_toArray()) {
			$response = $this->send(new Request($this->uri, 'GET'));

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (!is_array($response)) {
					throw new UnsupportedException('Получен не поддерживаемый формат ответа от API Диска.');
				}

				$this->setContents($response += [
					'free_space' => $response['total_space'] - $response['used_space']
				]);
			}
		}

		return $this->_toArray($allowed);
	}

	/**
	 * Работа с ресурсами на диске
	 *
	 * @param    string  $path Путь к новому либо уже существующему ресурсу
	 * @param    integer $limit
	 * @param    integer $offset
	 *
	 * @return   \Arhitector\Yandex\Disk\Resource\Closed
	 *
	 * @example
	 *
	 * $disk->getResource('any_file.ext') -> upload( __DIR__.'/file_to_upload');
	 * $disk->getResource('any_file.ext') // Mackey\Yandex\Disk\Resource\Closed
	 *      ->toArray(); // если ресурса еще нет, то исключение NotFoundException
	 *
	 * array (size=11)
	 * 'public_key' => string 'wICbu9SPnY3uT4tFA6P99YXJwuAr2TU7oGYu1fTq68Y=' (length=44)
	 * 'name' => string 'Gameface - Gangsigns_trapsound.ru.mp3' (length=37)
	 * 'created' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'public_url' => string 'https://yadi.sk/d/g0N4hNtXcrq22' (length=31)
	 * 'modified' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'media_type' => string 'audio' (length=5)
	 * 'path' => string 'disk:/applications_swagga/1/Gameface - Gangsigns_trapsound.ru.mp3' (length=65)
	 * 'md5' => string '8c2559f3ce1ece12e749f9e5dfbda59f' (length=32)
	 * 'type' => string 'file' (length=4)
	 * 'mime_type' => string 'audio/mpeg' (length=10)
	 * 'size' => int 8099883
	 */
	public function getResource($path, $limit = 20, $offset = 0)
	{
		if (!is_string($path)) {
			throw new \InvalidArgumentException('Ресурс, должен быть строкового типа - путь к файлу/папке.');
		}

		if (stripos($path, 'app:/') !== 0 && stripos($path, 'disk:/') !== 0) {
			$path = 'disk:/' . ltrim($path, ' /');
		}

		return (new Disk\Resource\Closed($path, $this, $this->uri))
			->setLimit($limit, $offset);
	}

	/**
	 * Список всех файлов.
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Collection
	 *
	 * @example
	 *
	 * $disk->getResources(100, 0) // Arhitector\Yandex\Disk\Resource\Collection
	 *      ->toArray();
	 *
	 * array (size=2)
	 * 0 => object(Arhitector\Yandex\Disk\Resource\Closed)[30]
	 * .....
	 */
	public function getResources($limit = 20, $offset = 0)
	{
		$callback = function ($parameters) {
			$response = $this->send(
				new Request(
					$this->uri
						->withPath($this->uri->getPath() . 'resources/files')
						->withQuery(http_build_query($parameters, '', '&')),
					'GET'
				)
			);

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['items'])) {
					return array_map(function ($item) {
						return new Disk\Resource\Closed($item, $this, $this->uri);
					}, $response['items']);
				}
			}

			return [];
		};

		return (new $this->collectionClass($callback))->setLimit($limit, $offset);
	}

	/**
	 * Работа с опубликованными ресурсами
	 *
	 * @param	mixed $public_key Публичный ключ к опубликованному ресурсу.
	 *
	 * @return	\Arhitector\Yandex\Disk\Resource\Opened
	 *
	 * @example
	 *
	 * $disk->getPublishResource('public_key') -> toArray()
	 *
	 * array (size=11)
	 * 'public_key' => string 'wICbu9SPnY3uT4tFA6P99YXJwuAr2TU7oGYu1fTq68Y=' (length=44)
	 * 'name' => string 'Gameface - Gangsigns_trapsound.ru.mp3' (length=37)
	 * 'created' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'public_url' => string 'https://yadi.sk/d/g0N4hNtXcrq22' (length=31)
	 * 'modified' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'media_type' => string 'audio' (length=5)
	 * 'path' => string 'disk:/applications_swagga/1/Gameface - Gangsigns_trapsound.ru.mp3' (length=65)
	 * 'md5' => string '8c2559f3ce1ece12e749f9e5dfbda59f' (length=32)
	 * 'type' => string 'file' (length=4)
	 * 'mime_type' => string 'audio/mpeg' (length=10)
	 * 'size' => int 8099883
	 */
	public function getPublishResource($public_key, $limit = 20, $offset = 0)
	{
		if (!is_string($public_key)) {
			throw new \InvalidArgumentException('Публичный ключ ресурса должен быть строкового типа.');
		}

		return (new Disk\Resource\Opened($public_key, $this, $this->uri))
			->setLimit($limit, $offset);
	}

	/**
	 * Получение списка опубликованных файлов и папок
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Collection
	 */
	public function getPublishResources($limit = 20, $offset = 0)
	{
		$callback = function ($parameters) {
			$previous = $this->setAccessTokenRequired(true);
			$response = $this->send(
				new Request(
					$this->uri
						->withPath($this->uri->getPath() . 'resources/public')
						->withQuery(http_build_query($parameters, '', '&')),
					'GET'
				)
			);
			$this->setAccessTokenRequired($previous);

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['items'])) {
					return array_map(function ($item) {
						return new Disk\Resource\Opened($item, $this, $this->uri);
					}, $response['items']);
				}
			}

			return [];
		};

		return (new $this->collectionClass($callback))->setLimit($limit, $offset);
	}

	/**
	 * Ресурсы в корзине.
	 *
	 * @param    string $path путь к файлу в корзине
	 * @param int       $limit
	 * @param int       $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Removed
	 * @example
	 *
	 * $disk->getTrashResource('file.ext') -> toArray() // файл в корзине
	 * $disk->getTrashResource('trash:/file.ext') -> delete()
	 */
	public function getTrashResource($path, $limit = 20, $offset = 0)
	{
		if (!is_string($path)) {
			throw new \InvalidArgumentException('Ресурс, должен быть строкового типа - путь к файлу/папке, либо NULL');
		}

		if (stripos($path, 'trash:/') === 0) {
			$path = substr($path, 7);
		}

		return (new Disk\Resource\Removed('trash:/' . ltrim($path, ' /'), $this, $this->uri))
			->setLimit($limit, $offset);
	}

	/**
	 * Содержимое всей корзины.
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Collection
	 */
	public function getTrashResources($limit = 20, $offset = 0)
	{
		$callback = function ($parameters) {
			if (
				!empty($parameters['sort'])
				&& !in_array($parameters['sort'], ['deleted', 'created', '-deleted', '-created'], true)
			) {
				throw new \UnexpectedValueException('Допустимые значения сортировки - deleted, created и со знаком "минус".');
			}

			$response = $this->send(
				new Request(
					$this->uri
						->withPath($this->uri->getPath() . 'trash/resources')
						->withQuery(http_build_query($parameters + ['path' => 'trash:/'], '', '&')),
					'GET'
				)
			);

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['_embedded']['items'])) {
					return array_map(function ($item) {
						return new Disk\Resource\Removed($item, $this, $this->uri);
					}, $response['_embedded']['items']);
				}
			}

			return [];
		};
	
		return (new $this->collectionClass($callback))->setSort('created')->setLimit($limit, $offset);
	}

	/**
	 * Очистить корзину.
	 *
	 * @return bool|\Arhitector\Yandex\Disk\Operation
	 */
	public function cleanTrash()
	{
		$response = $this->send(new Request($this->uri->withPath($this->uri->getPath() . 'trash/resources'), 'DELETE'));

		if ($response->getStatusCode() == 204) {
			$response = json_decode($response->getBody(), true);

			if (!empty($response['operation'])) {
				return $response['operation'];
			}

			return true;
		}

		return false;
	}

	/**
	 * Последние загруженные файлы
	 *
	 * @param    int $limit
	 * @param    int $offset
	 *
	 * @return   \Arhitector\Yandex\Disk\Resource\Collection
	 *
	 * @example
	 *
	 * $disk->uploaded(limit, offset) // коллекия закрытых ресурсов
	 */
	public function uploaded($limit = 20, $offset = 0)
	{
		$callback = function ($parameters) {
			$response = $this->send(
				new Request(
					$this->uri
						->withPath($this->uri->getPath() . 'resources/last-uploaded')
						->withQuery(http_build_query($parameters, '', '&')),
					'GET'
				)
			);

			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);

				if (isset($response['items'])) {
					return array_map(function ($item) {
						return new Disk\Resource\Closed($item, $this, $this->uri);
					}, $response['items']);
				}
			}

			return [];
		};

		return (new $this->collectionClass($callback))->setLimit($limit, $offset);
	}

	/**
	 * Получить статус операции.
	 *
	 * @param   string $identifier идентификатор операции или NULL
	 *
	 * @return  \Arhitector\Yandex\Disk\Operation
	 *
	 * @example
	 *
	 * $disk->getOperation('identifier operation')
	 */
	public function getOperation($identifier)
	{
		return new Disk\Operation($identifier, $this, $this->getUri());
	}

	/**
	 * Возвращает количество асинхронных операций экземпляра.
	 *
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return sizeof($this->getOperations());
	}

	/**
	 * Получить все операции, полученные во время выполнения сценария
	 *
	 * @return array
	 *
	 * @example
	 *
	 * $disk->getOperations()
	 *
	 * array (size=124)
	 *  0 => 'identifier_1',
	 *  1 => 'identifier_2',
	 *  2 => 'identifier_3',
	 */
	public function getOperations()
	{
		return $this->operations;
	}

	/**
	 * Отправляет запрос.
	 *
	 * @param \Psr\Http\Message\RequestInterface $request
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function send(RequestInterface $request)
	{
		$response = parent::send($request);

		if ($response->getStatusCode() == 202) {
			if (($responseBody = json_decode($response->getBody(), true)) && isset($responseBody['href'])) {
				$operation = new Uri($responseBody['href']);

				if (!$operation->getQuery()) {
					$responseBody['operation'] = substr(strrchr($operation->getPath(), '/'), 1);
					$stream = new Stream('php://temp', 'w');
					$stream->write(json_encode($responseBody));
					$this->addOperation($responseBody['operation']);

					return $response->withBody($stream);
				}
			}
		}

		return $response;
	}

	/**
	 * Этот экземпляр используется в качестве обёртки
	 *
	 * @return boolean
	 */
	public function isWrapper()
	{
		//return in_array(\Mackey\Yandex\Disk\Stream\Wrapper::SCHEME, stream_get_wrappers());
		return false;
	}

	/**
	 * Добавляет идентификатор операции в список.
	 *
	 * @param $identifier
	 *
	 * @return \Arhitector\Yandex\Disk
	 */
	protected function addOperation($identifier)
	{
		$this->operations[] = $identifier;

		return $this;
	}
}
