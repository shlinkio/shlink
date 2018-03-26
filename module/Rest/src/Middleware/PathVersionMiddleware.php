<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PathVersionMiddleware implements MiddlewareInterface
{
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
        $uri = $request->getUri();
        $path = $uri->getPath();

        // If the path does not begin with the version number, prepend v1 by default for BC compatibility purposes
        if (\strpos($path, '/v') !== 0) {
            $parts = \explode('/', $path);
            // Remove the first empty part and the
            \array_shift($parts);
            // Prepend the version prefix
            \array_unshift($parts, '/v1');

            $request = $request->withUri($uri->withPath(\implode('/', $parts)));
        }

        return $delegate->process($request);
    }
}
