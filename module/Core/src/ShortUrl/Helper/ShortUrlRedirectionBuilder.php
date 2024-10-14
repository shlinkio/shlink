<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectionResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

readonly class ShortUrlRedirectionBuilder implements ShortUrlRedirectionBuilderInterface
{
    public function __construct(
        private TrackingOptions $trackingOptions,
        private ShortUrlRedirectionResolverInterface $redirectionResolver,
    ) {
    }

    public function buildShortUrlRedirect(
        ShortUrl $shortUrl,
        ServerRequestInterface $request,
        ?string $extraPath = null,
    ): string {
        $uri = new Uri($this->redirectionResolver->resolveLongUrl($shortUrl, $request));
        $shouldForwardQuery = $shortUrl->forwardQuery();
        $baseQueryString = $uri->getQuery();
        $basePath = $uri->getPath();

        // Get current query by manually parsing query string, instead of using $request->getQueryParams().
        // That prevents some weird PHP logic in which some characters in param names are converted to ensure resulting
        // names are valid variable names.
        $currentQuery = Query::parse($request->getUri()->getQuery());

        return $uri
            ->withQuery($shouldForwardQuery ? $this->resolveQuery($baseQueryString, $currentQuery) : $baseQueryString)
            ->withPath($this->resolvePath($basePath, $extraPath))
            ->__toString();
    }

    private function resolveQuery(string $baseQueryString, array $currentQuery): string
    {
        $hardcodedQuery = Query::parse($baseQueryString);

        $disableTrackParam = $this->trackingOptions->disableTrackParam;
        if ($disableTrackParam !== null) {
            unset($currentQuery[$disableTrackParam]);
        }

        // We want to merge preserving numeric keys, as some params might be numbers
        $mergedQuery = ArrayUtils::merge($hardcodedQuery, $currentQuery, preserveNumericKeys: true);

        return Query::build($mergedQuery);
    }

    private function resolvePath(string $basePath, ?string $extraPath): string
    {
        return $extraPath === null ? $basePath : sprintf('%s%s', $basePath, $extraPath);
    }
}
