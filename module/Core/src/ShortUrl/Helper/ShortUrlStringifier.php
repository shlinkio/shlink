<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

class ShortUrlStringifier implements ShortUrlStringifierInterface
{
    public function __construct(private readonly array $domainConfig, private readonly string $basePath = '')
    {
    }

    public function stringify(ShortUrl $shortUrl): string
    {
        $uriWithoutShortCode = (new Uri())->withScheme($this->domainConfig['schema'] ?? 'http')
                                          ->withHost($this->resolveDomain($shortUrl))
                                          ->withPath($this->basePath)
                                          ->__toString();

        // The short code needs to be appended to avoid it from being URL-encoded
        return sprintf('%s/%s', $uriWithoutShortCode, $shortUrl->getShortCode());
    }

    private function resolveDomain(ShortUrl $shortUrl): string
    {
        return $shortUrl->getDomain()?->authority ?? $this->domainConfig['hostname'] ?? '';
    }
}
