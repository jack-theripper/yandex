<?php

namespace Arhitector\Yandex;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * The basic exception.
 *
 * @package Arhitector\Yandex
 */
class Exception extends \Exception implements RequestExceptionInterface
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Construct the exception.
     *
     * @param string            $message  [optional] The Exception message to throw.
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param Throwable         $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = '', RequestInterface $request, ResponseInterface $response, Throwable $previous = null)
    {
        parent::__construct($message, $response->getStatusCode(), $previous);

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @inheritDoc
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Returns the response object which was throwable exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

}
