<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        $authority = $request->getUri()->getAuthority();
        $domainSpecificRedirect = $this->resolveDomainSpecificRedirect($authority, $notFoundType);

        return $domainSpecificRedirect
            // If we did not find domain-specific redirects for current domain, we try to fall back to default redirects
            ?? $this->redirectResolver->resolveRedirectResponse($notFoundType, $this->redirectOptions)
            // Ultimately, we just call next handler if no domain-specific redirects or default redirects were found
            ?? $handler->handle($request);
    }

    private function resolveDomainSpecificRedirect(string $authority, NotFoundType $notFoundType): ?ResponseInterface
    {
        $domain = $this->domainService->findByAuthority($authority);
        return $domain === null ? null : $this->redirectResolver->resolveRedirectResponse($notFoundType, $domain);
    }
}
