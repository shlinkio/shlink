<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Transformer;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;

use function random_int;

class ShortUrlDataTransformerTest extends TestCase
{
    private ShortUrlDataTransformer $transformer;

    public function setUp(): void
    {
        $this->transformer = new ShortUrlDataTransformer([]);
    }

    /**
     * @test
     * @dataProvider provideShortUrls
     */
    public function properMetadataIsReturned(ShortUrl $shortUrl, array $expectedMeta): void
    {
        ['meta' => $meta] = $this->transformer->transform($shortUrl);

        $this->assertEquals($expectedMeta, $meta);
    }

    public function provideShortUrls(): iterable
    {
        $maxVisits = random_int(1, 1000);
        $now = Chronos::now();

        yield 'no metadata' => [new ShortUrl('', ShortUrlMeta::createEmpty()), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ]];
        yield 'max visits only' => [new ShortUrl('', ShortUrlMeta::createFromParams(null, null, null, $maxVisits)), [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => $maxVisits,
        ]];
        yield 'max visits and valid since' => [
            new ShortUrl('', ShortUrlMeta::createFromParams($now, null, null, $maxVisits)),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => null,
                'maxVisits' => $maxVisits,
            ],
        ];
        yield 'both dates' => [
            new ShortUrl('', ShortUrlMeta::createFromParams($now, $now->subDays(10))),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(10)->toAtomString(),
                'maxVisits' => null,
            ],
        ];
        yield 'everything' => [
            new ShortUrl('', ShortUrlMeta::createFromParams($now, $now->subDays(5), null, $maxVisits)),
            [
                'validSince' => $now->toAtomString(),
                'validUntil' => $now->subDays(5)->toAtomString(),
                'maxVisits' => $maxVisits,
            ],
        ];
    }
}
