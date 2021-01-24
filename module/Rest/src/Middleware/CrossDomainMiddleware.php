<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_merge;
use function implode;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (! $request->hasHeader('Origin')) {
            return $response;
        }

        // Add Allow-Origin header
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        if ($request->getMethod() !== self::METHOD_OPTIONS) {
            return $response;
        }

        return $this->addOptionsHeaders($request, $response);
    }

    private function addOptionsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // TODO This won't work. The route has to be matched from the router as this middleware needs to be executed
        //      before trying to match the route
        /** @var RouteResult|null $matchedRoute */
        $matchedRoute = $request->getAttribute(RouteResult::class);
        $matchedMethods = $matchedRoute !== null ? $matchedRoute->getAllowedMethods() : [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_PATCH,
            self::METHOD_DELETE,
            self::METHOD_OPTIONS,
        ];
        $corsHeaders = [
            'Access-Control-Allow-Methods' => implode(',', $matchedMethods),
            'Access-Control-Allow-Headers' => $request->getHeaderLine('Access-Control-Request-Headers'),
            'Access-Control-Max-Age' => $this->config['max_age'],
        ];

        // Options requests should always be empty and have a 204 status code
        return EmptyResponse::withHeaders(array_merge($response->getHeaders(), $corsHeaders));
    }
}
