<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function str_replace;

class ShortCodePathMiddleware implements MiddlewareInterface
{
    private const OLD_PATH_PREFIX = '/short-codes';
    private const NEW_PATH_PREFIX = '/short-urls';

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // If the path starts with the old prefix, replace it by the new one
        return $handler->handle(
            $request->withUri($uri->withPath(str_replace(self::OLD_PATH_PREFIX, self::NEW_PATH_PREFIX, $path)))
        );
    }
}
