<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use Laminas\Diactoros\Uri;

use function GuzzleHttp\Psr7\build_query;
use function sprintf;

trait NotFoundUrlHelpersTrait
{
    public function provideInvalidUrls(): iterable
    {
        yield 'invalid shortcode' => ['invalid', null, 'No URL found with short code "invalid"'];
        yield 'invalid shortcode + domain' => [
            'abc123',
            'example.com',
            'No URL found with short code "abc123" for domain "example.com"',
        ];
    }

    public function buildShortUrlPath(string $shortCode, ?string $domain, string $suffix = ''): string
    {
        $url = new Uri(sprintf('/short-urls/%s%s', $shortCode, $suffix));
        if ($domain !== null) {
            $url = $url->withQuery(build_query(['domain' => $domain]));
        }

        return (string) $url;
    }
}
