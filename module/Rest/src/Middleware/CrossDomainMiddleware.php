<?php
namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
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
        /** @var Response $response */
        $response = $delegate->process($request);
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
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS', // TODO Should be based on path
            'Access-Control-Max-Age' => '1000',
            'Access-Control-Allow-Headers' => $request->getHeaderLine('Access-Control-Request-Headers'),
        ] as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
