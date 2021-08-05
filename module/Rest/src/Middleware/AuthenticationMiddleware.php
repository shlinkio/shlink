<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;

use function Functional\contains;

class AuthenticationMiddleware implements MiddlewareInterface, StatusCodeInterface, RequestMethodInterface
{
    public const API_KEY_HEADER = 'X-Api-Key';

    public function __construct(
        private ApiKeyServiceInterface $apiKeyService,
        private array $routesWithoutApiKey,
        private array $routesWithQueryApiKey,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if (
            $routeResult === null
            || $routeResult->isFailure()
            || $request->getMethod() === self::METHOD_OPTIONS
            || contains($this->routesWithoutApiKey, $routeResult->getMatchedRouteName())
        ) {
            return $handler->handle($request);
        }

        $apiKey = $this->getApiKeyFromRequest($request, $routeResult);
        $result = $this->apiKeyService->check($apiKey);
        if (! $result->isValid()) {
            throw VerifyAuthenticationException::forInvalidApiKey();
        }

        return $handler->handle($request->withAttribute(ApiKey::class, $result->apiKey()));
    }

    public static function apiKeyFromRequest(Request $request): ApiKey
    {
        return $request->getAttribute(ApiKey::class);
    }

    private function getApiKeyFromRequest(ServerRequestInterface $request, RouteResult $routeResult): string
    {
        $routeName = $routeResult->getMatchedRouteName();
        $query = $request->getQueryParams();
        $isRouteWithApiKeyInQuery = contains($this->routesWithQueryApiKey, $routeName);
        $apiKey = $isRouteWithApiKeyInQuery ? ($query['apiKey'] ?? '') : $request->getHeaderLine(self::API_KEY_HEADER);

        if (empty($apiKey)) {
            throw $isRouteWithApiKeyInQuery
                ? MissingAuthenticationException::forQueryParam('apiKey')
                : MissingAuthenticationException::forHeaders([self::API_KEY_HEADER]);
        }

        return $apiKey;
    }
}
