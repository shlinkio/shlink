<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use GuzzleHttp\Psr7\Query;
use Laminas\Diactoros\Uri;

use function sprintf;

class UrlBuilder
{
    public static function buildShortUrlPath(string $shortCode, ?string $domain, string $suffix = ''): string
    {
        $url = new Uri(sprintf('/short-urls/%s%s', $shortCode, $suffix));
        if ($domain !== null) {
            $url = $url->withQuery(Query::build(['domain' => $domain]));
        }

        return $url->__toString();
    }
}
