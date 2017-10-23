<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Middleware;

use Doctrine\Common\Cache\Cache;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response as DiactResp;

class QrCodeCacheMiddleware implements MiddlewareInterface
{
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     *
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        $cacheKey = $request->getUri()->getPath();

        // If this QR code is already cached, just return it
        if ($this->cache->contains($cacheKey)) {
            $qrData = $this->cache->fetch($cacheKey);
            $response = new DiactResp();
            $response->getBody()->write($qrData['body']);
            return $response->withHeader('Content-Type', $qrData['content-type']);
        }

        // If not, call the next middleware and cache it
        /** @var Response $resp */
        $resp = $delegate->process($request);
        $this->cache->save($cacheKey, [
            'body' => $resp->getBody()->__toString(),
            'content-type' => $resp->getHeaderLine('Content-Type'),
        ]);
        return $resp;
    }
}
