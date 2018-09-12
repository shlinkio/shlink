<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Zend\Diactoros\Uri;

trait ShortUrlBuilderTrait
{
    private function buildShortUrl(array $domainConfig, string $shortCode): string
    {
        return (string) (new Uri())->withPath($shortCode)
                                   ->withScheme($domainConfig['schema'] ?? 'http')
                                   ->withHost($domainConfig['hostname'] ?? '');
    }
}
