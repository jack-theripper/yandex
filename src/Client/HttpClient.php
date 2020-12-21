<?php

/**
 * Часть библиотеки для работы с сервисами Яндекса
 *
 * @package    Arhitector\Yandex\Client
 * @version    2.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2016 Arhitector
 * @link       https://github.com/jack-theripper
 */
namespace Arhitector\Yandex\Client;

use Http\Client\Curl\CurlPromise;
use Http\Client\Curl\MultiRunner;
use Http\Client\Curl\PromiseCore;
use Http\Client\Curl\ResponseBuilder;
use Http\Client\Exception;
use Http\Client\Exception\RequestException;
use Http\Client\HttpClient as HttpClientInterface;
use Http\Client\HttpAsyncClient as HttpAsyncClientInterface;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-7 compatible cURL based HTTP client
 */
class HttpClient implements HttpClientInterface, HttpAsyncClientInterface
{

	/**
	 * @access private
	 */
	const DEPENDENCY_MSG = 'You should either provide $%s argument or install "php-http/discovery"';

	/**
	 * cURL options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * PSR-7 message factory
	 *
	 * @var MessageFactory
	 */
	protected $messageFactory;

	/**
	 * PSR-7 stream factory
	 *
	 * @var StreamFactory
	 */
	protected $streamFactory;

	/**
	 * cURL synchronous requests handle
	 *
	 * @var resource|null
	 */
	protected $handle = null;

	/**
	 * Simultaneous requests runner
	 *
	 * @var MultiRunner|null
	 */
	protected $multiRunner = null;


	/**
	 * Create new client
	 *
	 * @param MessageFactory $messageFactory HTTP Message factory
	 * @param StreamFactory  $streamFactory  HTTP Stream factory
	 * @param array          $options        cURL options (see http://php.net/curl_setopt)
	 *
	 * @throws \LogicException If some factory not provided and php-http/discovery not installed
	 */
	public function __construct(MessageFactory $messageFactory, StreamFactory $streamFactory, array $options = [])
	{
		$this->handle = curl_init();
		$this->messageFactory = $messageFactory;
		$this->streamFactory = $streamFactory;
		$this->options = $options;
	}

	/**
	 * Release resources if still active
	 */
	public function __destruct()
	{
		if (is_resource($this->handle))
		{
			curl_close($this->handle);
		}
	}

	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 *
	 * @param RequestInterface $request
	 *
	 * @return ResponseInterface
	 *
	 * @throws \RuntimeException         If creating the body stream fails.
	 * @throws \UnexpectedValueException if unsupported HTTP version requested
	 * @throws RequestException
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$responseBuilder = $this->createResponseBuilder();
		$options = $this->createCurlOptions($request, $responseBuilder);

		curl_reset($this->handle);
		curl_setopt_array($this->handle, $options);
		curl_exec($this->handle);

		if (curl_errno($this->handle) > 0) {
			throw new RequestException(curl_error($this->handle), $request);
		}

		$response = $responseBuilder->getResponse();
		$response->getBody()->seek(0);

		return $response;
	}

	/**
	 * Sends a PSR-7 request in an asynchronous way.
	 *
	 * @param RequestInterface $request
	 *
	 * @return Promise
	 *
	 * @throws \RuntimeException         If creating the body stream fails.
	 * @throws \UnexpectedValueException If unsupported HTTP version requested
	 * @throws Exception
	 *
	 * @since 1.0
	 */
	public function sendAsyncRequest(RequestInterface $request)
	{
		if ( ! $this->multiRunner instanceof MultiRunner)
		{
			$this->multiRunner = new MultiRunner();
		}

		$handle = curl_init();
		$responseBuilder = $this->createResponseBuilder();
		$options = $this->createCurlOptions($request, $responseBuilder);
		curl_setopt_array($handle, $options);

		$core = new PromiseCore($request, $handle, $responseBuilder);
		$promise = new CurlPromise($core, $this->multiRunner);
		$this->multiRunner->add($core);

		return $promise;
	}

	/**
	 * Generates cURL options
	 *
	 * @param RequestInterface $request
	 * @param ResponseBuilder  $responseBuilder
	 *
	 * @throws \UnexpectedValueException if unsupported HTTP version requested
	 * @throws \RuntimeException if can not read body
	 *
	 * @return array
	 */
	protected function createCurlOptions(RequestInterface $request, ResponseBuilder $responseBuilder)
	{
		$options = array_diff_key($this->options, array_flip([CURLOPT_INFILE, CURLOPT_INFILESIZE]));
		$options[CURLOPT_HTTP_VERSION] = $this->getCurlHttpVersion($request->getProtocolVersion());
		$options[CURLOPT_HEADERFUNCTION] = function ($ch, $data) use ($responseBuilder) {
			$str = trim($data);

			if ('' !== $str)
			{
				if (strpos(strtolower($str), 'http/') === 0)
				{
					$responseBuilder->setStatus($str)->getResponse();
				}
				else
				{
					$responseBuilder->addHeader($str);
				}
			}

			return strlen($data);
		};

		$options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
		$options[CURLOPT_URL] = (string) $request->getUri();
		$options[CURLOPT_HEADER] = false;

		if (in_array($request->getMethod(), ['GET', 'HEAD', 'TRACE', 'CONNECT']))
		{
			if ($request->getMethod() == 'HEAD')
			{
				$options[CURLOPT_NOBODY] = true;

				unset($options[CURLOPT_READFUNCTION], $options[CURLOPT_WRITEFUNCTION]);
			}
		}
		else
		{
			$options = $this->createCurlBody($request, $options);
		}

		$options[CURLOPT_WRITEFUNCTION] = function ($ch, $data) use ($responseBuilder) {
			return $responseBuilder->getResponse()->getBody()->write($data);
		};

		$options[CURLOPT_HTTPHEADER] = $this->createHeaders($request, $options);

		if ($request->getUri()->getUserInfo())
		{
			$options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();
		}

		$options[CURLOPT_FOLLOWLOCATION] = false;

		return $options;
	}

	/**
	 * Create headers array for CURLOPT_HTTPHEADER
	 *
	 * @param RequestInterface $request
	 * @param array $options cURL options
	 *
	 * @return string[]
	 */
	protected function createHeaders(RequestInterface $request, array $options)
	{
		$headers = [];
		$body = $request->getBody();
		$size = $body->getSize();

		foreach ($request->getHeaders() as $header => $values)
		{
			foreach ((array) $values as $value)
			{
				$headers[] = sprintf('%s: %s', $header, $value);
			}
		}

		if ( ! $request->hasHeader('Transfer-Encoding') && $size === null)
		{
			$headers[] = 'Transfer-Encoding: chunked';
		}

		if ( ! $request->hasHeader('Expect') && in_array($request->getMethod(), ['POST', 'PUT']))
		{
			if ($request->getProtocolVersion() < 2.0 && ! $body->isSeekable() || $size === null || $size > 1048576)
			{
				$headers[] = 'Expect: 100-Continue';
			}
			else
			{
				$headers[] = 'Expect: ';
			}
		}

		return $headers;
	}

	/**
	 * Create body
	 *
	 * @param RequestInterface $request
	 * @param array $options
	 *
	 * @return array
	 */
	protected function createCurlBody(RequestInterface $request, array $options)
	{
		$body = clone $request->getBody();
		$size = $body->getSize();

		// Avoid full loading large or unknown size body into memory. It doesn't replace "CURLOPT_READFUNCTION".
		if ($size === null || $size > 1048576)
		{
			if ($body->isSeekable())
			{
				$body->rewind();
			}

			$options[CURLOPT_UPLOAD] = true;

			if (isset($options[CURLOPT_READFUNCTION]) && is_callable($options[CURLOPT_READFUNCTION]))
			{
				$body = $body->detach();

				$options[CURLOPT_READFUNCTION] = function($curl_handler, $handler, $length) use ($body, $options) {
					return call_user_func($options[CURLOPT_READFUNCTION], $curl_handler, $body, $length);
				};
			}
			else
			{
				$options[CURLOPT_READFUNCTION] = function($curl, $handler, $length) use ($body) {
					return $body->read($length);
				};
			}
		}
		else
		{
			$options[CURLOPT_POSTFIELDS] = (string) $request->getBody();
		}

		return $options;
	}

	/**
	 * Return cURL constant for specified HTTP version
	 *
	 * @param string $version
	 *
	 * @throws \UnexpectedValueException if unsupported version requested
	 *
	 * @return int
	 */
	protected function getCurlHttpVersion($version)
	{
		if ($version == '1.1')
		{
			return CURL_HTTP_VERSION_1_1;
		}
		else
		{
			if ($version == '2.0')
			{
				if ( ! defined('CURL_HTTP_VERSION_2_0'))
				{
					throw new \UnexpectedValueException('libcurl 7.33 needed for HTTP 2.0 support');
				}

				return CURL_HTTP_VERSION_2_0;
			}
			else
			{
				return CURL_HTTP_VERSION_1_0;
			}
		}
	}

	/**
	 * Create new ResponseBuilder instance
	 *
	 * @return ResponseBuilder
	 *
	 * @throws \RuntimeException If creating the stream from $body fails.
	 */
	protected function createResponseBuilder()
	{
		try
		{
			$body = $this->streamFactory->createStream(fopen('php://temp', 'w+'));
		}
		catch (\InvalidArgumentException $e)
		{
			throw new \RuntimeException('Can not create "php://temp" stream.');
		}

		$response = $this->messageFactory->createResponse(200, null, [], $body);

		return new ResponseBuilder($response);
	}

}