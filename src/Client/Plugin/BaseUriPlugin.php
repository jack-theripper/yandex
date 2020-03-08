<?php
/**
 * This file is part of the arhitector/yandex-disk library.
 *
 * (c) Dmitry Arhitector <dmitry.arhitector@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arhitector\Yandex\Client\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Combines the AddHostPlugin and AddPathPlugin.
 */
class BaseUriPlugin implements Plugin
{

    /**
     * @var AddHostPlugin
     */
    protected $addHostPlugin;

    /**
     * @var AddPathPlugin
     */
    protected $addPathPlugin;

    /**
     * Combines the AddHostPlugin and AddPathPlugin.
     *
     * @param UriInterface $uri Has to contain a host name and cans have a path
     */
    public function __construct(UriInterface $uri)
    {
        $this->addHostPlugin = new AddHostPlugin($uri);

        if (rtrim($uri->getPath(), '/'))
        {
            $this->addPathPlugin = new AddPathPlugin($uri);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $addHostNext = function (RequestInterface $request) use ($next, $first) {
            return $this->addHostPlugin->handleRequest($request, $next, $first);
        };

        if ($this->addPathPlugin != null && ! $request->getUri()->getHost()) // проверить совпадает ли хост $uri с $request
        {
            return $this->addPathPlugin->handleRequest($request, $addHostNext, $first);
        }

        return $addHostNext($request);
    }

}
