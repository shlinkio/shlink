<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PathVersionMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // TODO Workaround... Do not process the request if it does not start with rest
        if (\strpos($path, '/rest') !== 0) {
            return $handler->handle($request);
        }

        // If the path does not begin with the version number, prepend v1 by default for BC compatibility purposes
        if (\strpos($path, '/rest/v') !== 0) {
            $parts = \explode('/', $path);
            // Remove the first empty part and the rest part
            \array_shift($parts);
            \array_shift($parts);
            // Prepend the version prefix
            \array_unshift($parts, '/rest/v1');

            $request = $request->withUri($uri->withPath(\implode('/', $parts)));
        }

        return $handler->handle($request);
    }
}
