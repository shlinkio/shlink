<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

class PublishingUpdatesGeneratorTest extends TestCase
{
    private PublishingUpdatesGenerator $generator;
    private Chronos $now;

    protected function setUp(): void
    {
        $this->now = Chronos::now();
        Chronos::setTestNow($this->now);

        $this->generator = new PublishingUpdatesGenerator(
            new ShortUrlDataTransformer(new ShortUrlStringifier()),
        );
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    #[Test, DataProvider('provideMethod')]
    public function visitIsProperlySerializedIntoUpdate(string $method, string $expectedTopic, ?string $title): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'customSlug' => 'foo',
            'longUrl' => 'https://longUrl',
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
                'longUrl' => 'https://longUrl',
                'dateCreated' => $this->now->toAtomString(),
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
                'visitsSummary' => VisitsSummary::fromTotalAndNonBots(0, 0),
                'hasRedirectRules' => false,
            ],
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $visit->date->toAtomString(),
                'potentialBot' => false,
                'visitedUrl' => '',
            ],
        ], $update->payload);
    }

    public static function provideMethod(): iterable
    {
        yield 'newVisitUpdate' => ['newVisitUpdate', 'https://shlink.io/new-visit', 'the cool title'];
        yield 'newShortUrlVisitUpdate' => ['newShortUrlVisitUpdate', 'https://shlink.io/new-visit/foo', null];
    }

    #[Test, DataProvider('provideOrphanVisits')]
    public function orphanVisitIsProperlySerializedIntoUpdate(Visit $orphanVisit): void
    {
        $update = $this->generator->newOrphanVisitUpdate($orphanVisit);

        self::assertEquals('https://shlink.io/new-orphan-visit', $update->topic);
        self::assertEquals([
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $orphanVisit->date->toAtomString(),
                'potentialBot' => false,
                'visitedUrl' => $orphanVisit->visitedUrl,
                'type' => $orphanVisit->type->value,
            ],
        ], $update->payload);
    }

    public static function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield VisitType::REGULAR_404->value => [Visit::forRegularNotFound($visitor)];
        yield VisitType::INVALID_SHORT_URL->value => [Visit::forInvalidShortUrl($visitor)];
        yield VisitType::BASE_URL->value => [Visit::forBasePath($visitor)];
    }

    #[Test]
    public function shortUrlIsProperlySerializedIntoUpdate(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'customSlug' => 'foo',
            'longUrl' => 'https://longUrl',
            'title' => 'The title',
        ]));

        $update = $this->generator->newShortUrlUpdate($shortUrl);

        self::assertEquals(Topic::NEW_SHORT_URL->value, $update->topic);
        self::assertEquals(['shortUrl' => [
            'shortCode' => $shortUrl->getShortCode(),
            'shortUrl' => 'http:/' . $shortUrl->getShortCode(),
            'longUrl' => 'https://longUrl',
            'dateCreated' => $this->now->toAtomString(),
            'tags' => [],
            'meta' => [
                'validSince' => null,
                'validUntil' => null,
                'maxVisits' => null,
            ],
            'domain' => null,
            'title' => 'The title',
            'crawlable' => false,
            'forwardQuery' => true,
            'visitsSummary' => VisitsSummary::fromTotalAndNonBots(0, 0),
            'hasRedirectRules' => false,
        ]], $update->payload);
    }
}
