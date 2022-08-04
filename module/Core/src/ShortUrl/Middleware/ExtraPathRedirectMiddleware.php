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
        private readonly ShortUrlResolverInterface $resolver,
        private readonly RequestTrackerInterface $requestTracker,
        private readonly ShortUrlRedirectionBuilderInterface $redirectionBuilder,
        private readonly RedirectResponseHelperInterface $redirectResponseHelper,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var NotFoundType|null $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);
        if (! $this->shouldApplyLogic($notFoundType)) {
            return $handler->handle($request);
        }

        $uri = $request->getUri();
        $query = $request->getQueryParams();
        [$potentialShortCode, $extraPath] = $this->resolvePotentialShortCodeAndExtraPath($uri);
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($potentialShortCode, $uri->getAuthority());

        try {
            // TODO Try pieces of the URL in order to match multi-segment slugs too
            $shortUrl = $this->resolver->resolveEnabledShortUrl($identifier);
            $this->requestTracker->trackIfApplicable($shortUrl, $request);

            $longUrl = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $query, $extraPath);
            return $this->redirectResponseHelper->buildRedirectResponse($longUrl);
        } catch (ShortUrlNotFoundException) {
            return $handler->handle($request);
        }
    }

    private function shouldApplyLogic(?NotFoundType $notFoundType): bool
    {
        if ($notFoundType === null || ! $this->urlShortenerOptions->appendExtraPath()) {
            return false;
        }

        return (
            // If multi-segment slugs are enabled, the appropriate not-found type is "invalid_short_url"
            $this->urlShortenerOptions->multiSegmentSlugsEnabled() && $notFoundType->isInvalidShortUrl()
        ) || (
            // If multi-segment slugs are disabled, the appropriate not-found type is "regular_404"
            ! $this->urlShortenerOptions->multiSegmentSlugsEnabled() && $notFoundType->isRegularNotFound()
        );
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
