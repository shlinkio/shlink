<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

class NotFoundRedirectHandler implements MiddlewareInterface
{
    private Options\NotFoundRedirectOptions $redirectOptions;
    private RedirectResponseHelperInterface $redirectResponseHelper;

    public function __construct(
        Options\NotFoundRedirectOptions $redirectOptions,
        RedirectResponseHelperInterface $redirectResponseHelper
    ) {
        $this->redirectOptions = $redirectOptions;
        $this->redirectResponseHelper = $redirectResponseHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var NotFoundType $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);

        if ($notFoundType->isBaseUrl() && $this->redirectOptions->hasBaseUrlRedirect()) {
            return $this->redirectResponseHelper->buildRedirectResponse($this->redirectOptions->getBaseUrlRedirect());
        }

        if ($notFoundType->isRegularNotFound() && $this->redirectOptions->hasRegular404Redirect()) {
            return $this->redirectResponseHelper->buildRedirectResponse(
                $this->redirectOptions->getRegular404Redirect(),
            );
        }

        if ($notFoundType->isInvalidShortUrl() && $this->redirectOptions->hasInvalidShortUrlRedirect()) {
            return $this->redirectResponseHelper->buildRedirectResponse(
                $this->redirectOptions->getInvalidShortUrlRedirect(),
            );
        }

        return $handler->handle($request);
    }
}
