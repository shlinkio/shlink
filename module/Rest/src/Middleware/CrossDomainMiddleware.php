<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;

use function implode;
use function strval;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    private string $cors_allow_credentials;
    private string $cors_allow_origin;
    private ?string $cors_allow_headers;
    private string $cors_max_age;

    public function __construct()
    {
        $this->cors_allow_credentials = EnvVars::CORS_ALLOW_CREDENTIALS->loadFromEnv() ? 'true' : 'false';
        $this->cors_allow_origin = EnvVars::CORS_ALLOW_ORIGIN->loadFromEnv();
        $this->cors_allow_headers = EnvVars::CORS_ALLOW_HEADERS->loadFromEnv();
        $this->cors_max_age = strval(EnvVars::CORS_MAX_AGE->loadFromEnv());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (! $request->hasHeader('Origin')) {
            return $response;
        }

        // Add Allow-Origin header
        $response = $response->withHeader('Access-Control-Allow-Origin', $this->cors_allow_origin);
        if ($request->getMethod() !== self::METHOD_OPTIONS) {
            return $response;
        }

        return $this->addOptionsHeaders($request, $response);
    }

    private function addOptionsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $corsHeaders = [
            'Access-Control-Allow-Methods' => $this->resolveCorsAllowedMethods($response),
            'Access-Control-Allow-Credentials' => $this->cors_allow_credentials,
            'Access-Control-Allow-Headers' => $this->cors_allow_headers === null ?
                $request->getHeaderLine('Access-Control-Request-Headers') : $this->cors_allow_headers,
            'Access-Control-Max-Age' => $this->cors_max_age,
        ];

        // Options requests should always be empty and have a 204 status code
        return EmptyResponse::withHeaders([...$response->getHeaders(), ...$corsHeaders]);
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
