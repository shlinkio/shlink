<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Transformer;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;

use function random_int;

class ShortUrlDataTransformerTest extends TestCase
{
    private ShortUrlDataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ShortUrlDataTransformer(new ShortUrlStringifier([]));
    }

    #[Test, DataProvider('provideShortUrls')]
    public function properMetadataIsReturned(ShortUrl $shortUrl, array $expectedMeta): void
    {
        ['meta' => $meta] = $this->transformer->transform($shortUrl);

        self::assertEquals($expectedMeta, $meta);
    }

    public static function provideShortUrls(): iterable
    {
        $maxVisits = random_int(1, 1000);
        $now = Chronos::now();

        yield 'no metadata' => [ShortUrl::createFake(), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ]];
        yield 'max visits only' => [ShortUrl::create(ShortUrlCreation::fromRawData([
            'maxVisits' => $maxVisits,
            'longUrl' => 'https://longUrl',
        ])), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => $maxVisits,
        ]];
        yield 'max visits and valid since' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(
                ['validSince' => $now, 'maxVisits' => $maxVisits, 'longUrl' => 'https://longUrl'],
            )),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => null,
                'maxVisits' => $maxVisits,
            ],
        ];
        yield 'both dates' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(
                ['validSince' => $now, 'validUntil' => $now->subDays(10), 'longUrl' => 'https://longUrl'],
            )),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(10)->toAtomString(),
                'maxVisits' => null,
            ],
        ];
        yield 'everything' => [
            ShortUrl::create(ShortUrlCreation::fromRawData([
                'validSince' => $now,
                'validUntil' => $now->subDays(5),
                'maxVisits' => $maxVisits,
                'longUrl' => 'https://longUrl',
            ])),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(5)->toAtomString(),
                'maxVisits' => $maxVisits,
            ],
        ];
    }

    #[Test]
    public function properTagsAreReturned(): void
    {
        ['tags' => $tags] = $this->transformer->transform(ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://longUrl',
            'tags' => ['foo', 'bar', 'baz'],
        ])));
        self::assertEquals(['foo', 'bar', 'baz'], $tags);
    }
}
