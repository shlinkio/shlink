<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Entity;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

use function Functional\every;
use function Functional\map;
use function range;
use function strlen;
use function strtolower;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

class ShortUrlTest extends TestCase
{
    #[Test, DataProvider('provideInvalidShortUrls')]
    public function regenerateShortCodeThrowsExceptionIfStateIsInvalid(
        ShortUrl $shortUrl,
        string $expectedMessage,
    ): void {
        $this->expectException(ShortCodeCannotBeRegeneratedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $shortUrl->regenerateShortCode(ShortUrlMode::STRICT);
    }

    public static function provideInvalidShortUrls(): iterable
    {
        yield 'with custom slug' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(['customSlug' => 'custom-slug', 'longUrl' => 'longUrl'])),
            'The short code cannot be regenerated on ShortUrls where a custom slug was provided.',
        ];
        yield 'already persisted' => [
            ShortUrl::createFake()->setId('1'),
            'The short code can be regenerated only on new ShortUrls which have not been persisted yet.',
        ];
    }

    #[Test, DataProvider('provideValidShortUrls')]
    public function regenerateShortCodeProperlyChangesTheValueOnValidShortUrls(
        ShortUrl $shortUrl,
    ): void {
        $firstShortCode = $shortUrl->getShortCode();

        $shortUrl->regenerateShortCode(ShortUrlMode::STRICT);
        $secondShortCode = $shortUrl->getShortCode();

        self::assertNotEquals($firstShortCode, $secondShortCode);
    }

    public static function provideValidShortUrls(): iterable
    {
        yield 'no custom slug' => [ShortUrl::createFake()];
        yield 'imported with custom slug' => [ShortUrl::fromImport(
            new ImportedShlinkUrl(ImportSource::BITLY, 'longUrl', [], Chronos::now(), null, 'custom-slug', null),
            true,
        )];
    }

    #[Test, DataProvider('provideLengths')]
    public function shortCodesHaveExpectedLength(?int $length, int $expectedLength): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(
            [ShortUrlInputFilter::SHORT_CODE_LENGTH => $length, 'longUrl' => 'longUrl'],
        ));

        self::assertEquals($expectedLength, strlen($shortUrl->getShortCode()));
    }

    public static function provideLengths(): iterable
    {
        yield [null, DEFAULT_SHORT_CODES_LENGTH];
        yield from map(range(4, 10), fn (int $value) => [$value, $value]);
    }

    #[Test]
    public function deviceLongUrlsAreUpdated(): void
    {
        $shortUrl = ShortUrl::withLongUrl('foo');

        $shortUrl->update(ShortUrlEdition::fromRawData([
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::ANDROID->value => 'android',
                DeviceType::IOS->value => 'ios',
            ],
        ]));
        self::assertEquals([
            DeviceType::ANDROID->value => 'android',
            DeviceType::IOS->value => 'ios',
            DeviceType::DESKTOP->value => null,
        ], $shortUrl->deviceLongUrls());

        $shortUrl->update(ShortUrlEdition::fromRawData([
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::ANDROID->value => null,
                DeviceType::DESKTOP->value => 'desktop',
            ],
        ]));
        self::assertEquals([
            DeviceType::ANDROID->value => null,
            DeviceType::IOS->value => 'ios',
            DeviceType::DESKTOP->value => 'desktop',
        ], $shortUrl->deviceLongUrls());

        $shortUrl->update(ShortUrlEdition::fromRawData([
            ShortUrlInputFilter::DEVICE_LONG_URLS => [
                DeviceType::ANDROID->value => null,
                DeviceType::IOS->value => null,
            ],
        ]));
        self::assertEquals([
            DeviceType::ANDROID->value => null,
            DeviceType::IOS->value => null,
            DeviceType::DESKTOP->value => 'desktop',
        ], $shortUrl->deviceLongUrls());
    }

    #[Test]
    public function generatesLowercaseOnlyShortCodesInLooseMode(): void
    {
        $range = range(1, 1000); // Use a "big" number to reduce false negatives
        $allFor = static fn (ShortUrlMode $mode): bool => every($range, static function () use ($mode): bool {
            $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(
                [ShortUrlInputFilter::LONG_URL => 'foo'],
                new UrlShortenerOptions(mode: $mode),
            ));
            $shortCode = $shortUrl->getShortCode();

            return $shortCode === strtolower($shortCode);
        });

        self::assertTrue($allFor(ShortUrlMode::LOOSE));
        self::assertFalse($allFor(ShortUrlMode::STRICT));
    }
}
