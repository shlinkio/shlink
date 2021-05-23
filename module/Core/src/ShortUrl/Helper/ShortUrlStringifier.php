<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

use function sprintf;

class ShortUrlStringifier implements ShortUrlStringifierInterface
{
    public function __construct(private array $domainConfig, private string $basePath = '')
    {
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

        return sprintf('%s%s', $domain->getAuthority(), $this->basePath);
    }
}
