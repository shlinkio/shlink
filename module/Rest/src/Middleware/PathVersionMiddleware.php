<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function strpos;

class PathVersionMiddleware implements MiddlewareInterface
{
    // TODO The /health endpoint needs this middleware in order to work without the version.
    //      Take it into account if this middleware is ever removed.
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // If the path does not begin with the version number, prepend v1 by default for BC purposes
        if (strpos($path, '/v') !== 0) {
            $request = $request->withUri($uri->withPath('/v1' . $uri->getPath()));
        }

        return $handler->handle($request);
    }
}
