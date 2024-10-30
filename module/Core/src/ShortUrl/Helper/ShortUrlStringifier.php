<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

use function sprintf;

readonly class ShortUrlStringifier implements ShortUrlStringifierInterface
{
    public function __construct(
        private UrlShortenerOptions $urlShortenerOptions = new UrlShortenerOptions(),
        private string $basePath = '',
    ) {
    }

    public function stringify(ShortUrl|ShortUrlIdentifier $shortUrl): string
    {
        $shortUrlIdentifier = $shortUrl instanceof ShortUrl ? ShortUrlIdentifier::fromShortUrl($shortUrl) : $shortUrl;
        $uriWithoutShortCode = (new Uri())->withScheme($this->urlShortenerOptions->schema)
                                          ->withHost($this->resolveDomain($shortUrlIdentifier))
                                          ->withPath($this->basePath)
                                          ->__toString();

        // The short code needs to be appended to avoid it from being URL-encoded
        return sprintf('%s/%s', $uriWithoutShortCode, $shortUrlIdentifier->shortCode);
    }

    private function resolveDomain(ShortUrlIdentifier $shortUrlIdentifier): string
    {
        return $shortUrlIdentifier->domain ?? $this->urlShortenerOptions->defaultDomain;
    }
}
