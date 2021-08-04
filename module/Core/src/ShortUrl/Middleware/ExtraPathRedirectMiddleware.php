<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use function array_pad;
use function explode;
use function sprintf;
use function trim;

class ExtraPathRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ShortUrlResolverInterface $resolver,
        private RequestTrackerInterface $requestTracker,
        private ShortUrlRedirectionBuilderInterface $redirectionBuilder,
        private RedirectResponseHelperInterface $redirectResponseHelper,
        private UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var NotFoundType|null $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);

        // We'll apply this logic only if actively opted in and current URL is potentially /{shortCode}/[...]
        if (! $notFoundType?->isRegularNotFound() || ! $this->urlShortenerOptions->appendExtraPath()) {
            return $handler->handle($request);
        }

        $uri = $request->getUri();
        $query = $request->getQueryParams();
        [$potentialShortCode, $extraPath] = $this->resolvePotentialShortCodeAndExtraPath($uri);
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($potentialShortCode, $uri->getAuthority());

        try {
            $shortUrl = $this->resolver->resolveEnabledShortUrl($identifier);
            $this->requestTracker->trackIfApplicable($shortUrl, $request);

            $longUrl = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $query, $extraPath);
            return $this->redirectResponseHelper->buildRedirectResponse($longUrl);
        } catch (ShortUrlNotFoundException) {
            return $handler->handle($request);
        }
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolvePotentialShortCodeAndExtraPath(UriInterface $uri): array
    {
        $pathParts = explode('/', trim($uri->getPath(), '/'), 2);
        [$potentialShortCode, $extraPath] = array_pad($pathParts, 2, null);

        return [$potentialShortCode, $extraPath === null ? null : sprintf('/%s', $extraPath)];
    }
}
