<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Matomo;

use Exception;
use MatomoTracker;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoTrackerBuilderInterface;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSender;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Core\Visit\Repository\VisitIterationRepositoryInterface;
use Shlinkio\Shlink\Geolocation\Model\Location;

use function array_values;
use function count;

class MatomoVisitSenderTest extends TestCase
{
    private MockObject&MatomoTrackerBuilderInterface $trackerBuilder;
    private Stub&VisitIterationRepositoryInterface $visitIterationRepository;

    #[Override]
    protected function setUp(): void
    {
        $this->trackerBuilder = $this->createMock(MatomoTrackerBuilderInterface::class);
        $this->visitIterationRepository = $this->createStub(VisitIterationRepositoryInterface::class);
    }

    /**
     * @param array<non-empty-string, string[]> $invokedMethods
     */
    #[Test, DataProvider('provideTrackerMethods')]
    public function visitIsSentToMatomo(Visit $visit, string|null $originalIpAddress, array $invokedMethods): void
    {
        $tracker = $this->createMock(MatomoTracker::class);
        $tracker->expects($this->once())->method('setUrl')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUserAgent')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUrlReferrer')->willReturn($tracker);
        $tracker
            ->expects($this->once())
            ->method('doTrackPageView')
            ->with($visit->shortUrl->title ?? '');
        $tracker
            ->expects($this->once())
            ->method('setForceVisitDateTime')
            ->with(
                $visit->date->setTimezone('UTC')->toDateTimeString(),
            );

        foreach ($invokedMethods as $invokedMethod => $args) {
            $tracker
                ->expects($this->once())
                ->method($invokedMethod)
                ->with(...array_values($args))
                ->willReturn(
                    $tracker,
                );
        }

        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);

        $this->visitSender()->sendVisit($visit, $originalIpAddress);
    }

    public static function provideTrackerMethods(): iterable
    {
        yield 'unlocated orphan visit' => [Visit::forBasePath(Visitor::empty()), null, []];
        yield 'located regular visit' => [
            Visit::forValidShortUrl(ShortUrl::withLongUrl('https://shlink.io'), Visitor::empty())
                ->locate(VisitLocation::fromLocation(new Location(
                    countryCode: 'US',
                    countryName: 'countryName',
                    regionName: 'regionName',
                    city: 'city',
                    latitude: 80.0,
                    longitude: 120.0,
                    timeZone: 'timeZone',
                ))),
            '1.2.3.4',
            [
                'setCity' => ['city'],
                'setCountry' => ['us'],
                'setLatitude' => [80.0],
                'setLongitude' => [120.0],
                'setIp' => ['1.2.3.4'],
            ],
        ];
        yield 'fallback IP' => [
            Visit::forBasePath(Visitor::fromParams(remoteAddress: '5.6.7.8')),
            null,
            ['setIp' => ['5.6.7.0']],
        ];
    }

    #[Test, DataProvider('provideUrlsToTrack')]
    public function properUrlIsTracked(Visit $visit, string $expectedTrackedUrl): void
    {
        $tracker = $this->createMock(MatomoTracker::class);
        $tracker->expects($this->once())->method('setUrl')->with($expectedTrackedUrl)->willReturn($tracker);
        $tracker->expects($this->once())->method('setUserAgent')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUrlReferrer')->willReturn($tracker);
        $tracker->expects($this->once())->method('doTrackPageView');
        $tracker->expects($this->never())->method('setCustomDimension');

        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);

        $this->visitSender()->sendVisit($visit);
    }

    public static function provideUrlsToTrack(): iterable
    {
        yield 'orphan visit without visited URL' => [Visit::forBasePath(Visitor::empty()), ''];
        yield 'orphan visit with visited URL' => [
            Visit::forBasePath(Visitor::fromParams(visitedUrl: 'https://s.test/foo')),
            'https://s.test/foo',
        ];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(
                ShortUrl::create(
                    new ShortUrlCreation('https://shlink.io', customSlug: 'bar'),
                ),
                Visitor::empty(),
            ),
            'http://s2.test/bar',
        ];
    }

    #[Test]
    public function multipleVisitsCanBeSent(): void
    {
        $dateRange = DateRange::allTime();
        $visitor = Visitor::empty();
        $bot = Visitor::botInstance();

        $this->visitIterationRepository
            ->method('findAllVisits')
            ->willReturn([
                Visit::forBasePath($bot),
                Visit::forValidShortUrl(ShortUrl::createFake(), $visitor),
                Visit::forInvalidShortUrl($visitor),
            ]);

        $tracker = $this->createStub(MatomoTracker::class);
        $tracker->method('setUrl')->willReturn($tracker);
        $tracker->method('setUserAgent')->willReturn($tracker);
        $tracker->method('setUrlReferrer')->willReturn($tracker);

        $callCount = 0;
        $this->trackerBuilder
            ->expects($this->exactly(3))
            ->method('buildMatomoTracker')
            ->willReturnCallback(
                static function () use (&$callCount, $tracker) {
                    $callCount++;

                    if ($callCount === 2) {
                        throw new Exception('Error');
                    }

                    return $tracker;
                },
            );

        $result = $this->visitSender()->sendVisitsInDateRange($dateRange);

        self::assertEquals(2, $result->successfulVisits);
        self::assertEquals(1, $result->failedVisits);
        self::assertCount(3, $result);
        self::assertTrue($result->hasSuccesses());
        self::assertTrue($result->hasFailures());
    }

    #[Test, DataProvider('provideOptionsWithDimensions')]
    public function customDimensionsAreSetWHenConfigured(
        Visit $visit,
        MatomoOptions $options,
        array $customDimensionCalls,
    ): void {
        $tracker = $this->createMock(MatomoTracker::class);
        $tracker->expects($this->once())->method('setUrl')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUserAgent')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUrlReferrer')->willReturn($tracker);
        $tracker->expects($this->once())->method('doTrackPageView');

        $index = 0;
        $tracker
            ->expects($this->exactly(count($customDimensionCalls)))
            ->method('setCustomDimension')
            ->willReturnCallback(static function (...$args) use ($customDimensionCalls, &$index) {
                self::assertSame($customDimensionCalls[$index++], $args);
            });

        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);

        $this->visitSender($options)->sendVisit($visit);
    }

    public static function provideOptionsWithDimensions(): iterable
    {
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(visitTypeDimensionId: 1),
            [
                [1, VisitType::BASE_URL->value],
            ],
        ];
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(isOrphanDimensionId: 2),
            [
                [2, 'true'],
            ],
        ];
        yield [
            Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::botInstance()),
            new MatomoOptions(isOrphanDimensionId: 2),
            [
                [2, 'false'],
            ],
        ];
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(visitDomainDimensionId: 5),
            [
                [5, 's2.test'],
            ],
        ];
        yield [
            Visit::forValidShortUrl(
                ShortUrl::create(new ShortUrlCreation(longUrl: 'https://example.com', domain: 's3.test')),
                Visitor::botInstance(),
            ),
            new MatomoOptions(visitDomainDimensionId: 5),
            [
                [5, 's3.test'],
            ],
        ];
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(visitTypeDimensionId: 1, isOrphanDimensionId: 5),
            [
                [1, VisitType::BASE_URL->value],
                [5, 'true'],
            ],
        ];
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(isOrphanDimensionId: 5, visitDomainDimensionId: 3),
            [
                [5, 'true'],
                [3, 's2.test'],
            ],
        ];
        yield [
            Visit::forBasePath(Visitor::botInstance()),
            new MatomoOptions(visitTypeDimensionId: 1, isOrphanDimensionId: 5, visitDomainDimensionId: 3),
            [
                [1, VisitType::BASE_URL->value],
                [5, 'true'],
                [3, 's2.test'],
            ],
        ];
    }

    private function visitSender(MatomoOptions $options = new MatomoOptions()): MatomoVisitSender
    {
        return new MatomoVisitSender(
            $this->trackerBuilder,
            $options,
            new ShortUrlStringifier(new UrlShortenerOptions(defaultDomain: 's2.test')),
            $this->visitIterationRepository,
        );
    }
}
