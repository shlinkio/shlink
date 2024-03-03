<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Entity;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

use function array_map;
use function range;
use function Shlinkio\Shlink\Core\ArrayUtils\every;
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
            ShortUrl::create(
                ShortUrlCreation::fromRawData(['customSlug' => 'custom-slug', 'longUrl' => 'https://longUrl']),
            ),
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
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://url', [], Chronos::now(), null, 'custom-slug', null),
            true,
        )];
    }

    #[Test, DataProvider('provideLengths')]
    public function shortCodesHaveExpectedLength(?int $length, int $expectedLength): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(
            [ShortUrlInputFilter::SHORT_CODE_LENGTH => $length, 'longUrl' => 'https://longUrl'],
        ));

        self::assertEquals($expectedLength, strlen($shortUrl->getShortCode()));
    }

    public static function provideLengths(): iterable
    {
        yield [null, DEFAULT_SHORT_CODES_LENGTH];
        yield from array_map(fn (int $value) => [$value, $value], range(4, 10));
    }

    #[Test]
    #[TestWith([null, '', 5])]
    #[TestWith(['foo bar/', 'foo-bar-', 13])]
    public function shortCodesHaveExpectedPrefix(
        ?string $pathPrefix,
        string $expectedPrefix,
        int $expectedShortCodeLength,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://longUrl',
            ShortUrlInputFilter::SHORT_CODE_LENGTH => 5,
            ShortUrlInputFilter::PATH_PREFIX => $pathPrefix,
        ]));
        $shortCode = $shortUrl->getShortCode();

        if (strlen($expectedPrefix) > 0) {
            self::assertStringStartsWith($expectedPrefix, $shortCode);
        }
        self::assertEquals($expectedShortCodeLength, strlen($shortCode));
    }

    #[Test]
    public function generatesLowercaseOnlyShortCodesInLooseMode(): void
    {
        $range = range(1, 1000); // Use a "big" number to reduce false negatives
        $allFor = static fn (ShortUrlMode $mode): bool => every($range, static function () use ($mode): bool {
            $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(
                [ShortUrlInputFilter::LONG_URL => 'https://foo'],
                new UrlShortenerOptions(mode: $mode),
            ));
            $shortCode = $shortUrl->getShortCode();

            return $shortCode === strtolower($shortCode);
        });

        self::assertTrue($allFor(ShortUrlMode::LOOSE));
        self::assertFalse($allFor(ShortUrlMode::STRICT));
    }
}
