<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

readonly class ShortUrlStringifier implements ShortUrlStringifierInterface
{
    public function __construct(
        private UrlShortenerOptions $urlShortenerOptions = new UrlShortenerOptions(),
        private string $basePath = '',
    ) {
    }

    public function stringify(ShortUrl $shortUrl): string
    {
        $domainConfig = $this->urlShortenerOptions->domain;
        $uriWithoutShortCode = (new Uri())->withScheme($domainConfig['schema'] ?? 'http')
                                          ->withHost($this->resolveDomain($shortUrl))
                                          ->withPath($this->basePath)
                                          ->__toString();

        // The short code needs to be appended to avoid it from being URL-encoded
        return sprintf('%s/%s', $uriWithoutShortCode, $shortUrl->getShortCode());
    }

    private function resolveDomain(ShortUrl $shortUrl): string
    {
        $domainConfig = $this->urlShortenerOptions->domain;
        return $shortUrl->getDomain()?->authority ?? $domainConfig['hostname'] ?? '';
    }
}
