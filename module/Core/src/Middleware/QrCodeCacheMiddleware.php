<?php
namespace Shlinkio\Shlink\Core\Middleware;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\Common\Cache\Cache;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class QrCodeCacheMiddleware implements MiddlewareInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * QrCodeCacheMiddleware constructor.
     * @param Cache $cache
     *
     * @Inject({Cache::class})
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $cacheKey = $request->getUri()->getPath();

        // If this QR code is already cached, just return it
        if ($this->cache->contains($cacheKey)) {
            $qrData = $this->cache->fetch($cacheKey);
            $response->getBody()->write($qrData['body']);
            return $response->withHeader('Content-Type', $qrData['content-type']);
        }

        // If not, call the next middleware and cache it
        /** @var Response $resp */
        $resp = $out($request, $response);
        $this->cache->save($cacheKey, [
            'body' => $resp->getBody()->__toString(),
            'content-type' => $resp->getHeaderLine('Content-Type'),
        ]);
        return $resp;
    }
}
