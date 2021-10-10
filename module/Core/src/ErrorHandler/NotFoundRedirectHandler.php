<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectResolverInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Options;

class NotFoundRedirectHandler implements MiddlewareInterface
{
    public function __construct(
        private Options\NotFoundRedirectOptions $redirectOptions,
        private NotFoundRedirectResolverInterface $redirectResolver,
        private DomainServiceInterface $domainService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var NotFoundType $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);
        $currentUri = $request->getUri();
        $domainSpecificRedirect = $this->resolveDomainSpecificRedirect($currentUri, $notFoundType);

        return $domainSpecificRedirect
            // If we did not find domain-specific redirects for current domain, we try to fall back to default redirects
            ?? $this->redirectResolver->resolveRedirectResponse($notFoundType, $this->redirectOptions, $currentUri)
            // Ultimately, we just call next handler if no domain-specific redirects or default redirects were found
            ?? $handler->handle($request);
    }

    private function resolveDomainSpecificRedirect(
        UriInterface $currentUri,
        NotFoundType $notFoundType,
    ): ?ResponseInterface {
        $domain = $this->domainService->findByAuthority($currentUri->getAuthority());
        if ($domain === null) {
            return null;
        }

        return $this->redirectResolver->resolveRedirectResponse($notFoundType, $domain, $currentUri);
    }
}
