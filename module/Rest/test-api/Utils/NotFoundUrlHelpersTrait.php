<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use GuzzleHttp\Psr7\Query;
use Laminas\Diactoros\Uri;

use function sprintf;

trait NotFoundUrlHelpersTrait
{
    public function provideInvalidUrls(): iterable
    {
        yield 'invalid shortcode' => ['invalid', null, 'No URL found with short code "invalid"', 'valid_api_key'];
        yield 'invalid shortcode without domain' => [
            'abc123',
            'example.com',
            'No URL found with short code "abc123" for domain "example.com"',
            'valid_api_key',
        ];
        yield 'invalid shortcode + domain' => [
            'custom-with-domain',
            'example.com',
            'No URL found with short code "custom-with-domain" for domain "example.com"',
            'valid_api_key',
        ];
        yield 'valid shortcode with invalid API key' => [
            'ghi789',
            null,
            'No URL found with short code "ghi789"',
            'author_api_key',
        ];
        yield 'valid shortcode + domain with invalid API key' => [
            'custom-with-domain',
            'some-domain.com',
            'No URL found with short code "custom-with-domain" for domain "some-domain.com"',
            'domain_api_key',
        ];
    }

    public function buildShortUrlPath(string $shortCode, ?string $domain, string $suffix = ''): string
    {
        $url = new Uri(sprintf('/short-urls/%s%s', $shortCode, $suffix));
        if ($domain !== null) {
            $url = $url->withQuery(Query::build(['domain' => $domain]));
        }

        return (string) $url;
    }
}
