<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use stdClass;

use function str_pad;

use const STR_PAD_BOTH;

class ShortUrlCreationTest extends TestCase
{
    #[Test, DataProvider('provideInvalidData')]
    public function exceptionIsThrownIfProvidedDataIsInvalid(array $data): void
    {
        $this->expectException(ValidationException::class);
        ShortUrlCreation::fromRawData($data);
    }

    public static function provideInvalidData(): iterable
    {
        yield [[]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::VALID_SINCE => '',
            ShortUrlInputFilter::VALID_UNTIL => '',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foobar',
            ShortUrlInputFilter::MAX_VISITS => 'invalid',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::VALID_SINCE => '2017',
            ShortUrlInputFilter::MAX_VISITS => 5,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::VALID_SINCE => new stdClass(),
            ShortUrlInputFilter::VALID_UNTIL => 'foo',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::VALID_UNTIL => 500,
            ShortUrlInputFilter::DOMAIN => 4,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::SHORT_CODE_LENGTH => 3,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::CUSTOM_SLUG => '',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::CUSTOM_SLUG => '   ',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => [],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => null,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'missing_schema',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                'invalid' => 'https://shlink.io',
            ],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::DESKTOP->value => '',
            ],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::DESKTOP->value => null,
            ],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::IOS->value => '   ',
            ],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::ANDROID->value => 'missing_schema',
            ],
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'https://foo',
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::IOS->value => 'https://bar',
                DeviceType::ANDROID->value => [],
            ],
        ]];
    }

    #[Test, DataProvider('provideCustomSlugs')]
    public function properlyCreatedInstanceReturnsValues(
        string $customSlug,
        string $expectedSlug,
        bool $multiSegmentEnabled = false,
        ShortUrlMode $shortUrlMode = ShortUrlMode::STRICT,
    ): void {
        $creation = ShortUrlCreation::fromRawData([
            'validSince' => Chronos::parse('2015-01-01')->toAtomString(),
            'customSlug' => $customSlug,
            'longUrl' => 'https://longUrl',
        ], new UrlShortenerOptions(multiSegmentSlugsEnabled: $multiSegmentEnabled, mode: $shortUrlMode));

        self::assertTrue($creation->hasValidSince());
        self::assertEquals(Chronos::parse('2015-01-01'), $creation->validSince);

        self::assertFalse($creation->hasValidUntil());
        self::assertNull($creation->validUntil);

        self::assertTrue($creation->hasCustomSlug());
        self::assertEquals($expectedSlug, $creation->customSlug);

        self::assertFalse($creation->hasMaxVisits());
        self::assertNull($creation->maxVisits);
    }

    public static function provideCustomSlugs(): iterable
    {
        yield ['🔥', '🔥'];
        yield ['🦣 🍅', '🦣-🍅'];
        yield ['🦣 🍅', '🦣-🍅', false, ShortUrlMode::LOOSE];
        yield ['foobar', 'foobar'];
        yield ['foo bar', 'foo-bar'];
        yield ['foo bar baz', 'foo-bar-baz'];
        yield ['foo bar-baz', 'foo-bar-baz'];
        yield ['foo BAR-baz', 'foo-bar-baz', false, ShortUrlMode::LOOSE];
        yield ['foo/bar/baz', 'foo/bar/baz', true];
        yield ['/foo/bar/baz', 'foo/bar/baz', true];
        yield ['/foo/baR/baZ', 'foo/bar/baz', true, ShortUrlMode::LOOSE];
        yield ['foo/bar/baz', 'foo-bar-baz'];
        yield ['/foo/bar/baz', '-foo-bar-baz'];
        yield ['wp-admin.php', 'wp-admin.php'];
        yield ['UPPER_lower', 'UPPER_lower'];
        yield ['UPPER_lower', 'upper_lower', false, ShortUrlMode::LOOSE];
        yield ['more~url_special.chars', 'more~url_special.chars'];
        yield ['구글', '구글'];
        yield ['グーグル', 'グーグル'];
        yield ['谷歌', '谷歌'];
        yield ['гугл', 'гугл'];
    }

    #[Test, DataProvider('provideTitles')]
    public function titleIsCroppedIfTooLong(?string $title, ?string $expectedTitle): void
    {
        $creation = ShortUrlCreation::fromRawData([
            'title' => $title,
            'longUrl' => 'https://longUrl',
        ]);

        self::assertEquals($expectedTitle, $creation->title);
    }

    public static function provideTitles(): iterable
    {
        yield [null, null];
        yield ['foo', 'foo'];
        yield [str_pad('bar', 600, ' ', STR_PAD_BOTH), 'bar'];
        yield [str_pad('', 511, 'a'), str_pad('', 511, 'a')];
        yield [str_pad('', 512, 'b'), str_pad('', 512, 'b')];
        yield [str_pad('', 513, 'c'), str_pad('', 512, 'c')];
        yield [str_pad('', 600, 'd'), str_pad('', 512, 'd')];
        yield [str_pad('', 800, 'e'), str_pad('', 512, 'e')];
    }

    #[Test, DataProvider('provideDomains')]
    public function emptyDomainIsDiscarded(?string $domain, ?string $expectedDomain): void
    {
        $creation = ShortUrlCreation::fromRawData([
            'domain' => $domain,
            'longUrl' => 'https://longUrl',
        ]);

        self::assertSame($expectedDomain, $creation->domain);
    }

    public static function provideDomains(): iterable
    {
        yield 'null domain' => [null, null];
        yield 'empty domain' => ['', null];
        yield 'trimmable domain' => ['   ', null];
        yield 'valid domain' => ['s.test', 's.test'];
    }
}
