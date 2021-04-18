<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\LocateVisit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class LocateVisitTest extends TestCase
{
    use ProphecyTrait;

    private LocateVisit $locateVisit;
    private ObjectProphecy $ipLocationResolver;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;
    private ObjectProphecy $dbUpdater;
    private ObjectProphecy $eventDispatcher;

    public function setUp(): void
    {
        $this->ipLocationResolver = $this->prophesize(IpLocationResolverInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);
        $this->dbUpdater->databaseFileExists()->willReturn(true);

        $this->locateVisit = new LocateVisit(
            $this->ipLocationResolver->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            $this->dbUpdater->reveal(),
            $this->eventDispatcher->reveal(),
        );
    }

    /** @test */
    public function invalidVisitLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn(null);
        $logWarning = $this->logger->warning('Tried to locate visit with id "{visitId}", but it does not exist.', [
            'visitId' => 123,
        ]);
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        $findVisit->shouldHaveBeenCalledOnce();
        $this->em->flush()->shouldNotHaveBeenCalled();
        $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->shouldNotHaveBeenCalled();
        $logWarning->shouldHaveBeenCalled();
        $dispatch->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function nonExistingGeoLiteDbLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', '')),
        );
        $dbExists = $this->dbUpdater->databaseFileExists()->willReturn(false);
        $logWarning = $this->logger->warning(
            'Tried to locate visit with id "{visitId}", but a GeoLite2 db was not found.',
            ['visitId' => 123],
        );
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        $findVisit->shouldHaveBeenCalledOnce();
        $dbExists->shouldHaveBeenCalledOnce();
        $this->em->flush()->shouldNotHaveBeenCalled();
        $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->shouldNotHaveBeenCalled();
        $logWarning->shouldHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function invalidAddressLogsWarning(): void
    {
        $event = new UrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', '')),
        );
        $resolveLocation = $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->willThrow(
            WrongIpException::class,
        );
        $logWarning = $this->logger->warning(
            'Tried to locate visit with id "{visitId}", but its address seems to be wrong. {e}',
            Argument::type('array'),
        );
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        $findVisit->shouldHaveBeenCalledOnce();
        $resolveLocation->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalled();
        $this->em->flush()->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function unhandledExceptionLogsError(): void
    {
        $event = new UrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn(
            Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', '')),
        );
        $resolveLocation = $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->willThrow(
            OutOfRangeException::class,
        );
        $logError = $this->logger->error(
            'An unexpected error occurred while trying to locate visit with id "{visitId}". {e}',
            Argument::type('array'),
        );
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        $findVisit->shouldHaveBeenCalledOnce();
        $resolveLocation->shouldHaveBeenCalledOnce();
        $logError->shouldHaveBeenCalled();
        $this->em->flush()->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideNonLocatableVisits
     */
    public function nonLocatableVisitsResolveToEmptyLocations(Visit $visit): void
    {
        $event = new UrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation(Argument::any());
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), VisitLocation::fromGeolocation(Location::emptyInstance()));
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
        $resolveIp->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    public function provideNonLocatableVisits(): iterable
    {
        $shortUrl = ShortUrl::createEmpty();

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
        $event = new UrlVisited('123', $originalIpAddress);

        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation($ipAddr)->willReturn($location);
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), VisitLocation::fromGeolocation($location));
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
        $resolveIp->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    public function provideIpAddresses(): iterable
    {
        yield 'no original IP address' => [
            Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', '')),
            null,
        ];
        yield 'original IP address' => [
            Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', '')),
            '1.2.3.4',
        ];
        yield 'base url' => [Visit::forBasePath(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
        yield 'invalid short url' => [Visit::forInvalidShortUrl(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
        yield 'regular not found' => [Visit::forRegularNotFound(new Visitor('', '', '1.2.3.4', '')), '1.2.3.4'];
    }
}
