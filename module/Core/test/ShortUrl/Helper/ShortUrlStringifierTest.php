<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;

class ShortUrlStringifierTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideConfigAndShortUrls
     */
    public function generatesExpectedOutputBasedOnConfigAndShortUrl(
        array $config,
        string $basePath,
        ShortUrl $shortUrl,
        string $expected,
    ): void {
        $stringifier = new ShortUrlStringifier($config, $basePath);

        self::assertEquals($expected, $stringifier->stringify($shortUrl));
    }

    public function provideConfigAndShortUrls(): iterable
    {
        $shortUrlWithShortCode = fn (string $shortCode, ?string $domain = null) => ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData([
                'longUrl' => '',
                'customSlug' => $shortCode,
                'domain' => $domain,
            ]),
        );

        yield 'no config' => [[], '', $shortUrlWithShortCode('foo'), 'http:/foo'];
        yield 'hostname in config' => [
            ['hostname' => 'example.com'],
            '',
            $shortUrlWithShortCode('bar'),
            'http://example.com/bar',
        ];
        yield 'special chars in short code' => [
            ['hostname' => 'example.com'],
            '',
            $shortUrlWithShortCode('グーグル'),
            'http://example.com/グーグル',
        ];
        yield 'emojis in short code' => [
            ['hostname' => 'example.com'],
            '',
            $shortUrlWithShortCode('🦣-🍅'),
            'http://example.com/🦣-🍅',
        ];
        yield 'hostname with base path in config' => [
            ['hostname' => 'example.com/foo/bar'],
            '',
            $shortUrlWithShortCode('abc'),
            'http://example.com/foo/bar/abc',
        ];
        yield 'full config' => [
            ['schema' => 'https', 'hostname' => 'foo.com'],
            '',
            $shortUrlWithShortCode('baz'),
            'https://foo.com/baz',
        ];
        yield 'custom domain' => [
            ['schema' => 'https', 'hostname' => 'foo.com'],
            '',
            $shortUrlWithShortCode('baz', 'mydom.es'),
            'https://mydom.es/baz',
        ];
        yield 'custom domain with base path' => [
            ['schema' => 'https', 'hostname' => 'foo.com'],
            '/foo/bar',
            $shortUrlWithShortCode('baz', 'mydom.es'),
            'https://mydom.es/foo/bar/baz',
        ];
    }
}
