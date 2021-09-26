<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

use function Functional\map;
use function range;
use function strlen;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

class ShortUrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidShortUrls
     */
    public function regenerateShortCodeThrowsExceptionIfStateIsInvalid(
        ShortUrl $shortUrl,
        string $expectedMessage,
    ): void {
        $this->expectException(ShortCodeCannotBeRegeneratedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $shortUrl->regenerateShortCode();
    }

    public function provideInvalidShortUrls(): iterable
    {
        yield 'with custom slug' => [
            ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['customSlug' => 'custom-slug', 'longUrl' => ''])),
            'The short code cannot be regenerated on ShortUrls where a custom slug was provided.',
        ];
        yield 'already persisted' => [
            ShortUrl::createEmpty()->setId('1'),
            'The short code can be regenerated only on new ShortUrls which have not been persisted yet.',
        ];
    }

    /**
     * @test
     * @dataProvider provideValidShortUrls
     */
    public function regenerateShortCodeProperlyChangesTheValueOnValidShortUrls(ShortUrl $shortUrl): void
    {
        $firstShortCode = $shortUrl->getShortCode();

        $shortUrl->regenerateShortCode();
        $secondShortCode = $shortUrl->getShortCode();

        self::assertNotEquals($firstShortCode, $secondShortCode);
    }

    public function provideValidShortUrls(): iterable
    {
        yield 'no custom slug' => [ShortUrl::createEmpty()];
        yield 'imported with custom slug' => [
            ShortUrl::fromImport(new ImportedShlinkUrl('', '', [], Chronos::now(), null, 'custom-slug', null), true),
        ];
    }

    /**
     * @test
     * @dataProvider provideLengths
     */
    public function shortCodesHaveExpectedLength(?int $length, int $expectedLength): void
    {
        $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(
            [ShortUrlInputFilter::SHORT_CODE_LENGTH => $length, 'longUrl' => ''],
        ));

        self::assertEquals($expectedLength, strlen($shortUrl->getShortCode()));
    }

    public function provideLengths(): iterable
    {
        yield [null, DEFAULT_SHORT_CODES_LENGTH];
        yield from map(range(4, 10), fn (int $value) => [$value, $value]);
    }
}
