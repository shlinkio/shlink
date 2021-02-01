<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Mercure;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Mercure\MercureUpdatesGenerator;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;

use function Shlinkio\Shlink\Common\json_decode;

class MercureUpdatesGeneratorTest extends TestCase
{
    private MercureUpdatesGenerator $generator;

    public function setUp(): void
    {
        $this->generator = new MercureUpdatesGenerator(new ShortUrlDataTransformer(new ShortUrlStringifier([])));
    }

    /**
     * @test
     * @dataProvider provideMethod
     */
    public function visitIsProperlySerializedIntoUpdate(string $method, string $expectedTopic): void
    {
        $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['customSlug' => 'foo', 'longUrl' => '']));
        $visit = new Visit($shortUrl, Visitor::emptyInstance());

        $update = $this->generator->{$method}($visit);

        self::assertEquals([$expectedTopic], $update->getTopics());
        self::assertEquals([
            'shortUrl' => [
                'shortCode' => $shortUrl->getShortCode(),
                'shortUrl' => 'http:/' . $shortUrl->getShortCode(),
                'longUrl' => '',
                'dateCreated' => $shortUrl->getDateCreated()->toAtomString(),
                'visitsCount' => 0,
                'tags' => [],
                'meta' => [
                    'validSince' => null,
                    'validUntil' => null,
                    'maxVisits' => null,
                ],
                'domain' => null,
            ],
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $visit->getDate()->toAtomString(),
            ],
        ], json_decode($update->getData()));
    }

    public function provideMethod(): iterable
    {
        yield 'newVisitUpdate' => ['newVisitUpdate', 'https://shlink.io/new-visit'];
        yield 'newShortUrlVisitUpdate' => ['newShortUrlVisitUpdate', 'https://shlink.io/new-visit/foo'];
    }
}
