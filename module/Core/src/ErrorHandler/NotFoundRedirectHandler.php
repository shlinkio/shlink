<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Options;

use function rtrim;

class NotFoundRedirectHandler implements MiddlewareInterface
{
    private Options\NotFoundRedirectOptions $redirectOptions;
    private string $shlinkBasePath;

    public function __construct(
        Options\NotFoundRedirectOptions $redirectOptions,
        string $shlinkBasePath
    ) {
        $this->redirectOptions = $redirectOptions;
        $this->shlinkBasePath = $shlinkBasePath;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));
        $redirectResponse = $this->createRedirectResponse($routeResult, $request->getUri());

        return $redirectResponse ?? $handler->handle($request);
    }

    private function createRedirectResponse(RouteResult $routeResult, UriInterface $uri): ?ResponseInterface
    {
        $isBaseUrl = rtrim($uri->getPath(), '/') === $this->shlinkBasePath;

        if ($isBaseUrl && $this->redirectOptions->hasBaseUrlRedirect()) {
            return new RedirectResponse($this->redirectOptions->getBaseUrlRedirect());
        }

        if (!$isBaseUrl && $routeResult->isFailure() && $this->redirectOptions->hasRegular404Redirect()) {
            return new RedirectResponse($this->redirectOptions->getRegular404Redirect());
        }

        if (
            $routeResult->isSuccess() &&
            $routeResult->getMatchedRouteName() === RedirectAction::class &&
            $this->redirectOptions->hasInvalidShortUrlRedirect()
        ) {
            return new RedirectResponse($this->redirectOptions->getInvalidShortUrlRedirect());
        }

        return null;
    }
}
