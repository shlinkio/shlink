<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;

use function Functional\map;
use function range;
use function strlen;

use const Shlinkio\Shlink\Core\DEFAULT_SHORT_CODES_LENGTH;

class ShortUrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidShortUrls
     */
    public function regenerateShortCodeThrowsExceptionIfStateIsInvalid(
        ShortUrl $shortUrl,
        string $expectedMessage
    ): void {
        $this->expectException(ShortCodeCannotBeRegeneratedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $shortUrl->regenerateShortCode();
    }

    public function provideInvalidShortUrls(): iterable
    {
        yield 'with custom slug' => [
            new ShortUrl('', ShortUrlMeta::fromRawData(['customSlug' => 'custom-slug'])),
            'The short code cannot be regenerated on ShortUrls where a custom slug was provided.',
        ];
        yield 'already persisted' => [
            (new ShortUrl(''))->setId('1'),
            'The short code can be regenerated only on new ShortUrls which have not been persisted yet.',
        ];
    }

    /** @test */
    public function regenerateShortCodeProperlyChangesTheValueOnValidShortUrls(): void
    {
        $shortUrl = new ShortUrl('');
        $firstShortCode = $shortUrl->getShortCode();

        $shortUrl->regenerateShortCode();
        $secondShortCode = $shortUrl->getShortCode();

        $this->assertNotEquals($firstShortCode, $secondShortCode);
    }

    /**
     * @test
     * @dataProvider provideLengths
     */
    public function shortCodesHaveExpectedLength(?int $length, int $expectedLength): void
    {
        $shortUrl = new ShortUrl('', ShortUrlMeta::fromRawData(
            [ShortUrlMetaInputFilter::SHORT_CODE_LENGTH => $length],
        ));

        $this->assertEquals($expectedLength, strlen($shortUrl->getShortCode()));
    }

    public function provideLengths(): iterable
    {
        yield [null, DEFAULT_SHORT_CODES_LENGTH];
        yield from map(range(4, 10), fn (int $value) => [$value, $value]);
    }

    /**
     * @test
     * @dataProvider provideCriteriaToMatch
     */
    public function criteriaIsMatchedWhenDatesMatch(ShortUrl $shortUrl, ShortUrlMeta $meta, bool $expected): void
    {
        $this->assertEquals($expected, $shortUrl->matchesCriteria($meta, []));
    }

    public function provideCriteriaToMatch(): iterable
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $end = Chronos::parse('2021-03-05 20:18:30');

        yield [new ShortUrl('foo'), ShortUrlMeta::fromRawData(['validSince' => $start]), false];
        yield [new ShortUrl('foo'), ShortUrlMeta::fromRawData(['validUntil' => $end]), false];
        yield [new ShortUrl('foo'), ShortUrlMeta::fromRawData(['validSince' => $start, 'validUntil' => $end]), false];
        yield [
            new ShortUrl('foo', ShortUrlMeta::fromRawData(['validSince' => $start])),
            ShortUrlMeta::fromRawData(['validSince' => $start]),
            true,
        ];
        yield [
            new ShortUrl('foo', ShortUrlMeta::fromRawData(['validUntil' => $end])),
            ShortUrlMeta::fromRawData(['validUntil' => $end]),
            true,
        ];
        yield [
            new ShortUrl('foo', ShortUrlMeta::fromRawData(['validUntil' => $end, 'validSince' => $start])),
            ShortUrlMeta::fromRawData(['validUntil' => $end, 'validSince' => $start]),
            true,
        ];
    }
}
