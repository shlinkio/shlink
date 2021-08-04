<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_merge;
use function implode;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    public function __construct(private array $config)
    {
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
        $corsHeaders = [
            'Access-Control-Allow-Methods' => $this->resolveCorsAllowedMethods($response),
            'Access-Control-Allow-Headers' => $request->getHeaderLine('Access-Control-Request-Headers'),
            'Access-Control-Max-Age' => $this->config['max_age'],
        ];

        // Options requests should always be empty and have a 204 status code
        return EmptyResponse::withHeaders(array_merge($response->getHeaders(), $corsHeaders));
    }

    private function resolveCorsAllowedMethods(ResponseInterface $response): string
    {
        // ImplicitOptionsMiddleware resolves allowed methods using the RouteResult request's attribute and sets them
        // in the "Allow" header.
        // If the header is there, we can re-use the value as it is.
        if ($response->hasHeader('Allow')) {
            return $response->getHeaderLine('Allow');
        }

        return implode(',', [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_PATCH,
            self::METHOD_DELETE,
        ]);
    }
}
