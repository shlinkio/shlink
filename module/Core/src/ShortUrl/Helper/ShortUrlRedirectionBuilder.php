<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use GuzzleHttp\Psr7\Query;
use Laminas\Stdlib\ArrayUtils;
use League\Uri\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

class ShortUrlRedirectionBuilder implements ShortUrlRedirectionBuilderInterface
{
    public function __construct(private readonly TrackingOptions $trackingOptions)
    {
    }

    public function buildShortUrlRedirect(
        ShortUrl $shortUrl,
        ServerRequestInterface $request,
        ?string $extraPath = null,
    ): string {
        $currentQuery = $request->getQueryParams();
        $device = DeviceType::matchFromUserAgent($request->getHeaderLine('User-Agent'));
        $uri = Uri::createFromString($shortUrl->longUrlForDevice($device));
        $shouldForwardQuery = $shortUrl->forwardQuery();

        return $uri
            ->withQuery($shouldForwardQuery ? $this->resolveQuery($uri, $currentQuery) : $uri->getQuery())
            ->withPath($this->resolvePath($uri, $extraPath))
            ->__toString();
    }

    private function resolveQuery(Uri $uri, array $currentQuery): ?string
    {
        $hardcodedQuery = Query::parse($uri->getQuery() ?? '');

        $disableTrackParam = $this->trackingOptions->disableTrackParam;
        if ($disableTrackParam !== null) {
            unset($currentQuery[$disableTrackParam]);
        }

        // We want to merge preserving numeric keys, as some params might be numbers
        $mergedQuery = ArrayUtils::merge($hardcodedQuery, $currentQuery, true);

        return empty($mergedQuery) ? null : Query::build($mergedQuery);
    }

    private function resolvePath(Uri $uri, ?string $extraPath): string
    {
        $hardcodedPath = $uri->getPath();
        return $extraPath === null ? $hardcodedPath : sprintf('%s%s', $hardcodedPath, $extraPath);
    }
}
