<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Authentication;
use Zend\Diactoros\Response\EmptyResponse;;
use Zend\Expressive\Router\RouteResult;

use function array_merge;
use function implode;

class CrossDomainMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (! $request->hasHeader('Origin')) {
            return $response;
        }

        // Add Allow-Origin header
        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'))
                             ->withHeader('Access-Control-Expose-Headers', implode(', ', [
                                 Authentication\Plugin\ApiKeyHeaderPlugin::HEADER_NAME,
                                 Authentication\Plugin\AuthorizationHeaderPlugin::HEADER_NAME,
                             ]));
        if ($request->getMethod() !== self::METHOD_OPTIONS) {
            return $response;
        }

        return $this->addOptionsHeaders($request, $response);
    }

    private function addOptionsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
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
            'Access-Control-Max-Age' => '1000',
            'Access-Control-Allow-Headers' => $request->getHeaderLine('Access-Control-Request-Headers'),
        ];

        // Options requests should always be empty and have a 204 status code
        return EmptyResponse::withHeaders(array_merge($response->getHeaders(), $corsHeaders));
    }
}
