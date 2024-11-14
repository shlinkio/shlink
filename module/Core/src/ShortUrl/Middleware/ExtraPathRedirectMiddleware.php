<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function array_slice;
use function count;
use function explode;
use function implode;
use function sprintf;
use function trim;

readonly class ExtraPathRedirectMiddleware implements MiddlewareInterface
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
        if (! $this->shouldApplyLogic($notFoundType)) {
            return $handler->handle($request);
        }

        return $this->tryToResolveRedirect($request, $handler);
    }

    private function shouldApplyLogic(NotFoundType|null $notFoundType): bool
    {
        if ($notFoundType === null || ! $this->urlShortenerOptions->appendExtraPath) {
            return false;
        }

        return (
            // If multi-segment slugs are enabled, the appropriate not-found type is "invalid_short_url"
            $this->urlShortenerOptions->multiSegmentSlugsEnabled && $notFoundType->isInvalidShortUrl()
        ) || (
            // If multi-segment slugs are disabled, the appropriate not-found type is "regular_404"
            ! $this->urlShortenerOptions->multiSegmentSlugsEnabled && $notFoundType->isRegularNotFound()
        );
    }

    private function tryToResolveRedirect(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        int $shortCodeSegments = 1,
    ): ResponseInterface {
        $uri = $request->getUri();
        [$potentialShortCode, $extraPath] = $this->resolvePotentialShortCodeAndExtraPath($uri, $shortCodeSegments);
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($potentialShortCode, $uri->getAuthority());

        try {
            $shortUrl = $this->resolver->resolveEnabledShortUrl($identifier);
            $visit = $this->requestTracker->trackIfApplicable($shortUrl, $request);

            $longUrl = $this->redirectionBuilder->buildShortUrlRedirect(
                $shortUrl,
                $request->withAttribute(Location::class, $visit?->getVisitLocation()),
                $extraPath,
            );
            return $this->redirectResponseHelper->buildRedirectResponse($longUrl);
        } catch (ShortUrlNotFoundException) {
            if ($extraPath === null || ! $this->urlShortenerOptions->multiSegmentSlugsEnabled) {
                return $handler->handle($request);
            }

            return $this->tryToResolveRedirect($request, $handler, $shortCodeSegments + 1);
        }
    }

    /**
     * @return array{string, string|null}
     */
    private function resolvePotentialShortCodeAndExtraPath(UriInterface $uri, int $shortCodeSegments): array
    {
        $parts = explode('/', trim($uri->getPath(), '/'));
        $shortCode = array_slice($parts, 0, $shortCodeSegments);
        $extraPath = array_slice($parts, $shortCodeSegments);

        return [implode('/', $shortCode), count($extraPath) > 0 ? sprintf('/%s', implode('/', $extraPath)) : null];
    }
}
