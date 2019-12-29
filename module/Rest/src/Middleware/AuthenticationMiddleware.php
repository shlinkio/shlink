<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPluginInterface;
use Zend\Expressive\Router\RouteResult;

use function Functional\contains;

class AuthenticationMiddleware implements MiddlewareInterface, StatusCodeInterface, RequestMethodInterface
{
    private array $routesWhitelist;
    private RequestToHttpAuthPluginInterface $requestToAuthPlugin;

    public function __construct(RequestToHttpAuthPluginInterface $requestToAuthPlugin, array $routesWhitelist)
    {
        $this->routesWhitelist = $routesWhitelist;
        $this->requestToAuthPlugin = $requestToAuthPlugin;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if (
            $routeResult === null
            || $routeResult->isFailure()
            || $request->getMethod() === self::METHOD_OPTIONS
            || contains($this->routesWhitelist, $routeResult->getMatchedRouteName())
        ) {
            return $handler->handle($request);
        }

        $plugin = $this->requestToAuthPlugin->fromRequest($request);
        $plugin->verify($request);
        $response = $handler->handle($request);

        return $plugin->update($request, $response);
    }
}
