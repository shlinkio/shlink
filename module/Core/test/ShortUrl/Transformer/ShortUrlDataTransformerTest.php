<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Transformer;

use Cake\Chronos\Chronos;
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

    /**
     * @test
     * @dataProvider provideShortUrls
     */
    public function properMetadataIsReturned(ShortUrl $shortUrl, array $expectedMeta): void
    {
        ['meta' => $meta] = $this->transformer->transform($shortUrl);

        self::assertEquals($expectedMeta, $meta);
    }

    public function provideShortUrls(): iterable
    {
        $maxVisits = random_int(1, 1000);
        $now = Chronos::now();

        yield 'no metadata' => [ShortUrl::createEmpty(), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ]];
        yield 'max visits only' => [ShortUrl::create(ShortUrlCreation::fromRawData([
            'maxVisits' => $maxVisits,
            'longUrl' => '',
        ])), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => $maxVisits,
        ]];
        yield 'max visits and valid since' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(
                ['validSince' => $now, 'maxVisits' => $maxVisits, 'longUrl' => ''],
            )),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => null,
                'maxVisits' => $maxVisits,
            ],
        ];
        yield 'both dates' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(
                ['validSince' => $now, 'validUntil' => $now->subDays(10), 'longUrl' => ''],
            )),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(10)->toAtomString(),
                'maxVisits' => null,
            ],
        ];
        yield 'everything' => [
            ShortUrl::create(ShortUrlCreation::fromRawData(
                ['validSince' => $now, 'validUntil' => $now->subDays(5), 'maxVisits' => $maxVisits, 'longUrl' => ''],
            )),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(5)->toAtomString(),
                'maxVisits' => $maxVisits,
            ],
        ];
    }
}
