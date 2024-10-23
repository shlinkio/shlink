<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlStringifierTest extends TestCase
{
    /**
     * @param 'http'|'https' $schema
     */
    #[Test, DataProvider('provideConfigAndShortUrls')]
    public function generatesExpectedOutputBasedOnConfigAndShortUrl(
        string $defaultDomain,
        string $schema,
        string $basePath,
        ShortUrl $shortUrl,
        string $expected,
    ): void {
        $stringifier = new ShortUrlStringifier(new UrlShortenerOptions($defaultDomain, $schema), $basePath);

        self::assertEquals($expected, $stringifier->stringify($shortUrl));
    }

    public static function provideConfigAndShortUrls(): iterable
    {
        $shortUrlWithShortCode = fn (string $shortCode, ?string $domain = null) => ShortUrl::create(
            ShortUrlCreation::fromRawData([
                'longUrl' => 'https://longUrl',
                'customSlug' => $shortCode,
                'domain' => $domain,
            ]),
        );

        yield 'no default domain' => ['', 'http', '', $shortUrlWithShortCode('foo'), 'http:/foo'];
        yield 'default domain' => [
            'example.com',
            'http',
            '',
            $shortUrlWithShortCode('bar'),
            'http://example.com/bar',
        ];
        yield 'special chars in short code' => [
            'example.com',
            'http',
            '',
            $shortUrlWithShortCode('グーグル'),
            'http://example.com/グーグル',
        ];
        yield 'emojis in short code' => [
            'example.com',
            'http',
            '',
            $shortUrlWithShortCode('🦣-🍅'),
            'http://example.com/🦣-🍅',
        ];
        yield 'default domain with base path' => [
            'example.com/foo/bar',
            'http',
            '',
            $shortUrlWithShortCode('abc'),
            'http://example.com/foo/bar/abc',
        ];
        yield 'custom domain' => [
            'foo.com',
            'https',
            '',
            $shortUrlWithShortCode('baz', 'mydom.es'),
            'https://mydom.es/baz',
        ];
        yield 'custom domain with base path' => [
            'foo.com',
            'https',
            '/foo/bar',
            $shortUrlWithShortCode('baz', 'mydom.es'),
            'https://mydom.es/foo/bar/baz',
        ];
    }
}
