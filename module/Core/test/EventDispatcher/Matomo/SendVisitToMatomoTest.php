<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Matomo;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MatomoTracker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\Matomo\SendVisitToMatomo;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoTrackerBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class SendVisitToMatomoTest extends TestCase
{
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;
    private MockObject & MatomoTrackerBuilderInterface $trackerBuilder;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->trackerBuilder = $this->createMock(MatomoTrackerBuilderInterface::class);
    }

    #[Test]
    public function visitIsNotSentWhenMatomoIsDisabled(): void
    {
        $this->em->expects($this->never())->method('find');
        $this->trackerBuilder->expects($this->never())->method('buildMatomoTracker');
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        ($this->listener(enabled: false))(new VisitLocated('123'));
    }

    #[Test]
    public function visitIsNotSentWhenItDoesNotExist(): void
    {
        $this->em->expects($this->once())->method('find')->willReturn(null);
        $this->trackerBuilder->expects($this->never())->method('buildMatomoTracker');
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to send visit with id "{visitId}" to matomo, but it does not exist.',
            ['visitId' => '123'],
        );

        ($this->listener())(new VisitLocated('123'));
    }

    #[Test, DataProvider('provideTrackerMethods')]
    public function visitIsSentWhenItExists(Visit $visit, ?string $originalIpAddress, array $invokedMethods): void
    {
        $visitId = '123';

        $tracker = $this->createMock(MatomoTracker::class);
        $tracker->expects($this->once())->method('setUrl')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUserAgent')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUrlReferrer')->willReturn($tracker);
        $tracker->expects($this->once())->method('doTrackPageView')->with('');

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

        foreach ($invokedMethods as $invokedMethod) {
            $tracker->expects($this->once())->method($invokedMethod)->willReturn($tracker);
        }

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        ($this->listener())(new VisitLocated($visitId, $originalIpAddress));
    }

    public static function provideTrackerMethods(): iterable
    {
        yield 'unlocated orphan visit' => [Visit::forBasePath(Visitor::emptyInstance()), null, []];
        yield 'located regular visit' => [
            Visit::forValidShortUrl(ShortUrl::withLongUrl('https://shlink.io'), Visitor::emptyInstance())
                ->locate(VisitLocation::fromGeolocation(new Location(
                    countryCode: 'countryCode',
                    countryName: 'countryName',
                    regionName: 'regionName',
                    city: 'city',
                    latitude: 123,
                    longitude: 123,
                    timeZone: 'timeZone',
                ))),
            '1.2.3.4',
            ['setCity', 'setCountry', 'setLatitude', 'setLongitude', 'setIp'],
        ];
        yield 'fallback IP' => [Visit::forBasePath(new Visitor('', '', '1.2.3.4', '')), null, ['setIp']];
    }

    #[Test, DataProvider('provideUrlsToTrack')]
    public function properUrlIsTracked(Visit $visit, string $expectedTrackedUrl): void
    {
        $visitId = '123';

        $tracker = $this->createMock(MatomoTracker::class);
        $tracker->expects($this->once())->method('setUrl')->with($expectedTrackedUrl)->willReturn($tracker);
        $tracker->expects($this->once())->method('setUserAgent')->willReturn($tracker);
        $tracker->expects($this->once())->method('setUrlReferrer')->willReturn($tracker);
        $tracker->expects($this->any())->method('setCustomTrackingParameter')->willReturn($tracker);
        $tracker->expects($this->once())->method('doTrackPageView');

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willReturn($tracker);
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        ($this->listener())(new VisitLocated($visitId));
    }

    public static function provideUrlsToTrack(): iterable
    {
        yield 'orphan visit without visited URL' => [Visit::forBasePath(Visitor::emptyInstance()), ''];
        yield 'orphan visit with visited URL' => [
            Visit::forBasePath(new Visitor('', '', null, 'https://s.test/foo')),
            'https://s.test/foo',
        ];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(ShortUrl::create(
                ShortUrlCreation::fromRawData([
                    ShortUrlInputFilter::LONG_URL => 'https://shlink.io',
                    ShortUrlInputFilter::CUSTOM_SLUG => 'bar',
                ]),
            ), Visitor::emptyInstance()),
            'http://s2.test/bar',
        ];
    }

    #[Test]
    public function logsErrorWhenTrackingFails(): void
    {
        $visitId = '123';
        $e = new Exception('Error!');

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(
            $this->createMock(Visit::class),
        );
        $this->trackerBuilder->expects($this->once())->method('buildMatomoTracker')->willThrowException($e);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())->method('error')->with(
            'An error occurred while trying to send visit to Matomo. {e}',
            ['e' => $e],
        );

        ($this->listener())(new VisitLocated($visitId));
    }

    private function listener(bool $enabled = true): SendVisitToMatomo
    {
        return new SendVisitToMatomo(
            $this->em,
            $this->logger,
            new ShortUrlStringifier(['hostname' => 's2.test']),
            new MatomoOptions(enabled: $enabled),
            $this->trackerBuilder,
        );
    }
}
