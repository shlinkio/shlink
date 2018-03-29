<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        /** @var Response $response */
        $response = $handler->handle($request);
        if (! $request->hasHeader('Origin')) {
            return $response;
        }

        // Add Allow-Origin header
        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'))
                             ->withHeader('Access-Control-Expose-Headers', 'Authorization');
        if ($request->getMethod() !== self::METHOD_OPTIONS) {
            return $response;
        }

        // Add OPTIONS-specific headers
        foreach ([
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS', // TODO Should be dynamic
//            'Access-Control-Allow-Methods' => $response->getHeaderLine('Allow'),
            'Access-Control-Max-Age' => '1000',
            'Access-Control-Allow-Headers' => $request->getHeaderLine('Access-Control-Request-Headers'),
        ] as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
