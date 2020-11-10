<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function rtrim;

class NotFoundRedirectHandler implements MiddlewareInterface
{
    private Options\NotFoundRedirectOptions $redirectOptions;
    private RedirectResponseHelperInterface $redirectResponseHelper;
    private string $shlinkBasePath;

    public function __construct(
        Options\NotFoundRedirectOptions $redirectOptions,
        RedirectResponseHelperInterface $redirectResponseHelper,
        string $shlinkBasePath
    ) {
        $this->redirectOptions = $redirectOptions;
        $this->shlinkBasePath = $shlinkBasePath;
        $this->redirectResponseHelper = $redirectResponseHelper;
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
            return $this->redirectResponseHelper->buildRedirectResponse($this->redirectOptions->getBaseUrlRedirect());
        }

        if (!$isBaseUrl && $routeResult->isFailure() && $this->redirectOptions->hasRegular404Redirect()) {
            return $this->redirectResponseHelper->buildRedirectResponse(
                $this->redirectOptions->getRegular404Redirect(),
            );
        }

        if (
            $routeResult->isSuccess() &&
            $routeResult->getMatchedRouteName() === RedirectAction::class &&
            $this->redirectOptions->hasInvalidShortUrlRedirect()
        ) {
            return $this->redirectResponseHelper->buildRedirectResponse(
                $this->redirectOptions->getInvalidShortUrlRedirect(),
            );
        }

        return null;
    }
}
