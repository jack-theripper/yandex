<?php

namespace Arhitector\Yandex\Client\Plugin;

use Arhitector\Yandex\Client\Exception\ForbiddenException;
use Arhitector\Yandex\Exception\NotFoundException;
use Arhitector\Yandex\Exception\ServerException;
use Arhitector\Yandex\Client\Exception\UnauthorizedException;
use Arhitector\Yandex\Exception\ClientException;
use Http\Client\Common\Exception\ServerErrorException;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Transforms the response into exceptions.
 *
 * @package Arhitector\Yandex\Client\Plugin
 */
class ResponseErrorPlugin implements Plugin
{
    
    /**
     * @var string[]
     */
    protected $rules = [
        
        // Неавторизован
        401 => UnauthorizedException::class,
        
        // Доступ запрещён. Возможно, у приложения недостаточно прав для данного действия.
        // Пользователь заблокирован.
        // API недоступно. Ваши файлы занимают больше места, чем у вас есть. Удалите лишнее или увеличьте объём Диска.
        // Не достаточно прав для изменения данных в общей папке.
        403 => ForbiddenException::class,
        
        // Не удалось найти запрошенный ресурс.
        404 => NotFoundException::class,
        
        // Указанного пути "{path}" не существует.
        // По указанному пути "{path}" уже существует папка с таким именем.
        // Ресурс "{path}" уже существует.
        //409 => ,
        
        // Ресурс заблокирован. Возможно, над ним выполняется другая операция.
        //423 => ,
        
        // Слишком много запросов.
        //429 => TooManyRequestsException::class,
        
        // У владельца общей папки недостаточно свободного места.
        // Недостаточно свободного места.
        //507 => ,
        
        /**
         * Некорректные данные (Bad Request).
         */
        // 400: defaultdict(lambda: BadRequestError,  {"FieldValidationError": FieldValidationError}),
        //400 => 'Arhitector\Yandex\Client\Exception\UnsupportedException',
        
        
        /**
         * Доступ запрещён (Forbidden).
         * Возможно, у приложения недостаточно прав для данного действия.
         */
        // 403: defaultdict(lambda: ForbiddenError),
        //403 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',
        
        // Не удалось найти запрошенный ресурс (Not Found).
        // 404: defaultdict(lambda: NotFoundError, {"DiskNotFoundError": PathNotFoundError}),
        // 404 => 'Arhitector\Yandex\Exception\NotFoundException',
        
        // client
        // Ресурс не может быть представлен в запрошенном формате (Not Acceptable).
        // 406: defaultdict(lambda: NotAcceptableError),
        // 406 => 'Arhitector\Yandex\Disk\Exception\UnsupportedException',
        
        /**
         * Конфликт путей/имён.
         */
        //        409: defaultdict(lambda: ConflictError,
        //                                  {"DiskPathDoesntExistsError": ParentNotFoundError,
        //                                   "DiskPathPointsToExistentDirectoryError": DirectoryExistsError,
        //                                   "DiskResourceAlreadyExistsError": PathExistsError,
        //                                   "MD5DifferError": MD5DifferError}),
        //        409 => [
        //
        //            /**
        //             * Указанного пути не существует.
        //             */
        //            'DiskPathDoesntExistsError'              => 'Arhitector\Yandex\Exception\NotFoundException',
        //
        //            /**
        //             * Ресурс уже существует
        //             */
        //            'DiskResourceAlreadyExistsError'         => 'Arhitector\Yandex\Disk\Exception\AlreadyExistsException',
        //
        //            /**
        //             * Уже существует папка с таким именем.
        //             */
        //            'DiskPathPointsToExistentDirectoryError' => 'Arhitector\Yandex\Disk\Exception\AlreadyExistsException'
        //        ],
        //
        /**
         * Ресурс не может быть представлен в запрошенном формате (Unsupported Media Type).
         */
        // 415: defaultdict(lambda: UnsupportedMediaError),
        //   415 => 'Arhitector\Yandex\Client\Exception\UnsupportedException',
        
        /**
         * Ресурс заблокирован (Locked).
         * Возможно, над ним выполняется другая операция.
         */
        // 423: defaultdict(lambda: LockedError,    {"DiskResourceLockedError": ResourceIsLockedError}),
        // 423 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',
        
        // 429: defaultdict(lambda: TooManyRequestsError),
        // Слишком много запросов(Too Many Requests).
        // 429 => 'Arhitector\Yandex\Client\Exception\ForbiddenException',
        
        // 500: defaultdict(lambda: InternalServerError),
        // 502: defaultdict(lambda: BadGatewayError),
        
        // Server
        // 503: defaultdict(lambda: UnavailableError),
        // Сервис временно недоступен(Service Unavailable).
        // 503 => 'Arhitector\Yandex\Client\Exception\ServiceException',
        
        // 504: defaultdict(lambda: GatewayTimeoutError),
        
        // Недостаточно свободного места (Insufficient Storage).
        // 507 => 'Arhitector\Yandex\Disk\Exception\OutOfSpaceException',
        
        // 509: defaultdict(lambda: InsufficientStorageError)}
        
        //https://github.com/ivknv/yadisk/tree/master/yadisk
        
        //   * Too many requests(429).
        //* The resource is locked (423). Perhaps another operation is being performed on it.
    ];
    
    /**
     * Constructor.
     */
    public function __construct()
    {
    
    }
    
    /**
     * Handle the request and return the response coming from the next callable.
     *
     * @see http://docs.php-http.org/en/latest/plugins/build-your-own.html
     *
     * @param RequestInterface $request
     * @param callable         $next  Next middleware in the chain, the request is passed as the first argument
     * @param callable         $first First middleware in the chain, used to to restart a request
     *
     * @return Promise Resolves a PSR-7 Response or fails with an Http\Client\Exception (The same as HttpAsyncClient)
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        /** @var Promise $promise */
        $promise = $next($request);
        
        return $promise->then(function (ResponseInterface $response) use ($request) {
            return $this->transformResponseToException($request, $response);
        });
    }
    
    /**
     * Transform response to an error if possible.
     *
     * @param RequestInterface  $request  Request of the call
     * @param ResponseInterface $response Response of the call
     *
     * @return ResponseInterface If status code is not in 4xx or 5xx return response
     *
     * @throws ClientException If response status code is a 4xx
     * @throws ServerErrorException If response status code is a 5xx
     */
    protected function transformResponseToException(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Try to processing as json
        if (isset($this->rules[$response->getStatusCode()]) && stripos($response->getHeaderLine('Content-Type'), 'json') !== false)
        {
            $jsonResponse = json_decode($response->getBody(), true);
            $classException = $this->rules[$response->getStatusCode()];
            
            if ($jsonResponse && $classException)
            {
                throw new $classException($jsonResponse['message'] ?? (string) $response->getBody(), $request, $response);
            }
        }
        
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500)
        {
            throw new ClientException($response->getReasonPhrase(), $request, $response);
        }
        
        if ($response->getStatusCode() >= 500 && $response->getStatusCode() < 600)
        {
            throw new ServerException($response->getReasonPhrase(), $request, $response);
        }
        
        return $response;
    }
    
}
