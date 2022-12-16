<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGenerator;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;

class PublishingUpdatesGeneratorTest extends TestCase
{
    private PublishingUpdatesGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PublishingUpdatesGenerator(
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
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'customSlug' => 'foo',
            'longUrl' => '',
            'title' => $title,
        ]));
        $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());

        /** @var Update $update */
        $update = $this->generator->{$method}($visit);

        self::assertEquals($expectedTopic, $update->topic);
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
                'visitsSummary' => [
                    'total' => 0,
                    'nonBots' => 0,
                    'bots' => 0,
                ],
            ],
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $visit->getDate()->toAtomString(),
                'potentialBot' => false,
            ],
        ], $update->payload);
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

        self::assertEquals('https://shlink.io/new-orphan-visit', $update->topic);
        self::assertEquals([
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $orphanVisit->getDate()->toAtomString(),
                'potentialBot' => false,
                'visitedUrl' => $orphanVisit->visitedUrl(),
                'type' => $orphanVisit->type()->value,
            ],
        ], $update->payload);
    }

    public function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield VisitType::REGULAR_404->value => [Visit::forRegularNotFound($visitor)];
        yield VisitType::INVALID_SHORT_URL->value => [Visit::forInvalidShortUrl($visitor)];
        yield VisitType::BASE_URL->value => [Visit::forBasePath($visitor)];
    }

    /** @test */
    public function shortUrlIsProperlySerializedIntoUpdate(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'customSlug' => 'foo',
            'longUrl' => '',
            'title' => 'The title',
        ]));

        $update = $this->generator->newShortUrlUpdate($shortUrl);

        self::assertEquals(Topic::NEW_SHORT_URL->value, $update->topic);
        self::assertEquals(['shortUrl' => [
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
            'title' => $shortUrl->title(),
            'crawlable' => false,
            'forwardQuery' => true,
            'visitsSummary' => [
                'total' => 0,
                'nonBots' => 0,
                'bots' => 0,
            ],
        ]], $update->payload);
    }
}
