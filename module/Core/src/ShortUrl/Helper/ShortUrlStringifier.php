<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

class ShortUrlStringifier implements ShortUrlStringifierInterface
{
    private array $domainConfig;

    public function __construct(array $domainConfig)
    {
        $this->domainConfig = $domainConfig;
    }

    public function stringify(ShortUrl $shortUrl): string
    {
        return (new Uri())->withPath($shortUrl->getShortCode())
                          ->withScheme($this->domainConfig['schema'] ?? 'http')
                          ->withHost($this->resolveDomain($shortUrl))
                          ->__toString();
    }

    private function resolveDomain(ShortUrl $shortUrl): string
    {
        $domain = $shortUrl->getDomain();
        if ($domain === null) {
            return $this->domainConfig['hostname'] ?? '';
        }

        return $domain->getAuthority();
    }
}
