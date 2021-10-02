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
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;

use function Shlinkio\Shlink\Common\json_decode;

class MercureUpdatesGeneratorTest extends TestCase
{
    private MercureUpdatesGenerator $generator;

    public function setUp(): void
    {
        $this->generator = new MercureUpdatesGenerator(
            new ShortUrlDataTransformer(new ShortUrlStringifier([])),
            new OrphanVisitDataTransformer(),
        );
    }

    /**
     * @test
     * @dataProvider provideMethod
     */
    public function visitIsProperlySerializedIntoUpdate(string $method, string $expectedTopic, ?string $title): void
    {
        $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
            'customSlug' => 'foo',
            'longUrl' => '',
            'title' => $title,
        ]));
        $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());

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
                'title' => $title,
                'crawlable' => false,
                'forwardQuery' => true,
            ],
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $visit->getDate()->toAtomString(),
                'potentialBot' => false,
            ],
        ], json_decode($update->getData()));
    }

    public function provideMethod(): iterable
    {
        yield 'newVisitUpdate' => ['newVisitUpdate', 'https://shlink.io/new-visit', 'the cool title'];
        yield 'newShortUrlVisitUpdate' => ['newShortUrlVisitUpdate', 'https://shlink.io/new-visit/foo', null];
    }

    /**
     * @test
     * @dataProvider provideOrphanVisits
     */
    public function orphanVisitIsProperlySerializedIntoUpdate(Visit $orphanVisit): void
    {
        $update = $this->generator->newOrphanVisitUpdate($orphanVisit);

        self::assertEquals(['https://shlink.io/new-orphan-visit'], $update->getTopics());
        self::assertEquals([
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $orphanVisit->getDate()->toAtomString(),
                'potentialBot' => false,
                'visitedUrl' => $orphanVisit->visitedUrl(),
                'type' => $orphanVisit->type(),
            ],
        ], json_decode($update->getData()));
    }

    public function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield Visit::TYPE_REGULAR_404 => [Visit::forRegularNotFound($visitor)];
        yield Visit::TYPE_INVALID_SHORT_URL => [Visit::forInvalidShortUrl($visitor)];
        yield Visit::TYPE_BASE_URL => [Visit::forBasePath($visitor)];
    }
}
