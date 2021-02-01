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
        ShortUrl $shortUrl,
        string $expected
    ): void {
        $stringifier = new ShortUrlStringifier($config);

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

        yield 'no config' => [[], $shortUrlWithShortCode('foo'), 'http:/foo'];
        yield 'hostname in config' => [
            ['hostname' => 'example.com'],
            $shortUrlWithShortCode('bar'),
            'http://example.com/bar',
        ];
        yield 'full config' => [
            ['schema' => 'https', 'hostname' => 'foo.com'],
            $shortUrlWithShortCode('baz'),
            'https://foo.com/baz',
        ];
        yield 'custom domain' => [
            ['schema' => 'https', 'hostname' => 'foo.com'],
            $shortUrlWithShortCode('baz', 'mydom.es'),
            'https://mydom.es/baz',
        ];
    }
}
