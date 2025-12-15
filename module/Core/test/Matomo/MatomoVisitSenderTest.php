<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Matomo;

use Exception;
use MatomoTracker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoTrackerBuilderInterface;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSender;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitIterationRepositoryInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function array_values;

class MatomoVisitSenderTest extends TestCase
{
    private MockObject & MatomoTrackerBuilderInterface $trackerBuilder;
    private Stub & VisitIterationRepositoryInterface $visitIterationRepository;
    private MatomoVisitSender $visitSender;

    protected function setUp(): void
    {
        $this->trackerBuilder = $this->createMock(MatomoTrackerBuilderInterface::class);
        $this->visitIterationRepository = $this->createStub(VisitIterationRepositoryInterface::class);

        $this->visitSender = new MatomoVisitSender(
            $this->trackerBuilder,
            new ShortUrlStringifier(new UrlShortenerOptions(defaultDomain: 's2.test')),
            $this->visitIterationRepository,
        );
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
        $tracker->expects($this->once())->method('doTrackPageView')->with($visit->shortUrl?->title() ?? '');
        $tracker->expects($this->once())->method('setForceVisitDateTime')->with(
            $visit->date->setTimezone('UTC')->toDateTimeString(),
        );

        if ($visit->isOrphan()) {
            $tracker->expects($this->exactly(2))->method('setCustomTrackingParameter')->willReturnMap([
                ['type', $visit->type->value, $tracker],
                ['orphan', 'true', $tracker],
            ]);
        } else {
            $tracker->expects($this->once())->method('setCustomTrackingParameter')->with(
                'type',
                $visit->type->value,
            )->willReturn($tracker);
        }

        foreach ($invokedMethods as $invokedMethod => $args) {
            $tracker->expects($this->once())->method($invokedMethod)->with(...array_values($args))->willReturn(
                $tracker,
            );
        }

        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);

        $this->visitSender->sendVisit($visit, $originalIpAddress);
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
                    latitude: 123,
                    longitude: 456,
                    timeZone: 'timeZone',
                ))),
            '1.2.3.4',
            [
                'setCity' => ['city'],
                'setCountry' => ['us'],
                'setLatitude' => [123],
                'setLongitude' => [456],
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
        $tracker->expects($this->any())->method('setCustomTrackingParameter')->willReturn($tracker);
        $tracker->expects($this->once())->method('doTrackPageView');
        $tracker->expects($this->once())->method('setForceVisitDateTime')->with(
            $visit->date->setTimezone('UTC')->toDateTimeString(),
        );

        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);

        $this->visitSender->sendVisit($visit);
    }

    public static function provideUrlsToTrack(): iterable
    {
        yield 'orphan visit without visited URL' => [Visit::forBasePath(Visitor::empty()), ''];
        yield 'orphan visit with visited URL' => [
            Visit::forBasePath(Visitor::fromParams(visitedUrl: 'https://s.test/foo')),
            'https://s.test/foo',
        ];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(ShortUrl::create(
                ShortUrlCreation::fromRawData([
                    ShortUrlInputFilter::LONG_URL => 'https://shlink.io',
                    ShortUrlInputFilter::CUSTOM_SLUG => 'bar',
                ]),
            ), Visitor::empty()),
            'http://s2.test/bar',
        ];
    }

    #[Test]
    public function multipleVisitsCanBeSent(): void
    {
        $dateRange = DateRange::allTime();
        $visitor = Visitor::empty();
        $bot = Visitor::botInstance();

        $this->visitIterationRepository->method('findAllVisits')->with($dateRange)->willReturn([
            Visit::forBasePath($bot),
            Visit::forValidShortUrl(ShortUrl::createFake(), $visitor),
            Visit::forInvalidShortUrl($visitor),
        ]);

        $tracker = $this->createStub(MatomoTracker::class);
        $tracker->method('setUrl')->willReturn($tracker);
        $tracker->method('setUserAgent')->willReturn($tracker);
        $tracker->method('setUrlReferrer')->willReturn($tracker);
        $tracker->method('setCustomTrackingParameter')->willReturn($tracker);

        $callCount = 0;
        $this->trackerBuilder->expects($this->exactly(3))->method('buildMatomoTracker')->willReturnCallback(
            function () use (&$callCount, $tracker) {
                $callCount++;

                if ($callCount === 2) {
                    throw new Exception('Error');
                }

                return $tracker;
            },
        );

        $result = $this->visitSender->sendVisitsInDateRange($dateRange);

        self::assertEquals(2, $result->successfulVisits);
        self::assertEquals(1, $result->failedVisits);
        self::assertCount(3, $result);
        self::assertTrue($result->hasSuccesses());
        self::assertTrue($result->hasFailures());
    }
}
