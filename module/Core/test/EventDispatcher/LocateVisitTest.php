<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use OutOfRangeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\LocateVisit;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class LocateVisitTest extends TestCase
{
    private LocateVisit $locateVisit;
    private MockObject & IpLocationResolverInterface $ipLocationResolver;
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;
    private MockObject & EventDispatcherInterface $eventDispatcher;
    private MockObject & DbUpdaterInterface $dbUpdater;

    protected function setUp(): void
    {
        $this->ipLocationResolver = $this->createMock(IpLocationResolverInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);

        $this->locateVisit = new LocateVisit(
            $this->ipLocationResolver,
            $this->em,
            $this->logger,
            $this->dbUpdater,
            $this->eventDispatcher,
        );
    }

    /** @test */
    public function invalidVisitLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn(null);
        $this->em->expects($this->never())->method('flush');
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to locate visit with id "{visitId}", but it does not exist.',
            ['visitId' => 123],
        );
        $this->eventDispatcher->expects($this->never())->method('dispatch')->with(new VisitLocated('123'));
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');

        ($this->locateVisit)($event);
    }

    /** @test */
    public function nonExistingGeoLiteDbLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', '')),
        );
        $this->em->expects($this->never())->method('flush');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->withAnyParameters()->willReturn(false);
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to locate visit with id "{visitId}", but a GeoLite2 db was not found.',
            ['visitId' => 123],
        );
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(new VisitLocated('123'));
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');

        ($this->locateVisit)($event);
    }

    /** @test */
    public function invalidAddressLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', '')),
        );
        $this->em->expects($this->never())->method('flush');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->withAnyParameters()->willReturn(true);
        $this->ipLocationResolver->expects(
            $this->once(),
        )->method('resolveIpLocation')->withAnyParameters()->willThrowException(WrongIpException::fromIpAddress(''));
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to locate visit with id "{visitId}", but its address seems to be wrong. {e}',
            $this->isType('array'),
        );
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(new VisitLocated('123'));

        ($this->locateVisit)($event);
    }

    /** @test */
    public function unhandledExceptionLogsError(): void
    {
        $event = new UrlVisited('123');
        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', '')),
        );
        $this->em->expects($this->never())->method('flush');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->withAnyParameters()->willReturn(true);
        $this->ipLocationResolver->expects(
            $this->once(),
        )->method('resolveIpLocation')->withAnyParameters()->willThrowException(new OutOfRangeException());
        $this->logger->expects($this->once())->method('error')->with(
            'An unexpected error occurred while trying to locate visit with id "{visitId}". {e}',
            $this->isType('array'),
        );
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(new VisitLocated('123'));

        ($this->locateVisit)($event);
    }

    /**
     * @test
     * @dataProvider provideNonLocatableVisits
     */
    public function nonLocatableVisitsResolveToEmptyLocations(Visit $visit): void
    {
        $event = new UrlVisited('123');
        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn($visit);
        $this->em->expects($this->once())->method('flush');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->withAnyParameters()->willReturn(true);
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(new VisitLocated('123'));
        $this->logger->expects($this->never())->method('warning');

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), VisitLocation::fromGeolocation(Location::emptyInstance()));
    }

    public static function provideNonLocatableVisits(): iterable
    {
        $shortUrl = ShortUrl::createFake();

        yield 'null IP' => [Visit::forValidShortUrl($shortUrl, new Visitor('', '', null, ''))];
        yield 'empty IP' => [Visit::forValidShortUrl($shortUrl, new Visitor('', '', '', ''))];
        yield 'localhost' => [Visit::forValidShortUrl($shortUrl, new Visitor('', '', IpAddress::LOCALHOST, ''))];
    }

    /**
     * @test
     * @dataProvider provideIpAddresses
     */
    public function locatableVisitsResolveToLocation(Visit $visit, ?string $originalIpAddress): void
    {
        $ipAddr = $originalIpAddress ?? $visit->getRemoteAddr();
        $location = new Location('', '', '', '', 0.0, 0.0, '');
        $event = UrlVisited::withOriginalIpAddress('123', $originalIpAddress);

        $this->em->expects($this->once())->method('find')->with(Visit::class, '123')->willReturn($visit);
        $this->em->expects($this->once())->method('flush');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->withAnyParameters()->willReturn(true);
        $this->ipLocationResolver->expects($this->once())->method('resolveIpLocation')->with($ipAddr)->willReturn(
            $location,
        );

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(new VisitLocated('123'));
        $this->logger->expects($this->never())->method('warning');

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), VisitLocation::fromGeolocation($location));
    }

    public static function provideIpAddresses(): iterable
    {
        yield 'no original IP address' => [
            Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', '')),
            null,
        ];
        yield 'original IP address' => [
            Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', '')),
            '1.2.3.4',
        ];
        yield 'base url' => [Visit::forBasePath(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
        yield 'invalid short url' => [Visit::forInvalidShortUrl(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
        yield 'regular not found' => [Visit::forRegularNotFound(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
    }
}
