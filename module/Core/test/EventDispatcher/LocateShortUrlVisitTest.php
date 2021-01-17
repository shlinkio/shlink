<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\LocateShortUrlVisit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class LocateShortUrlVisitTest extends TestCase
{
    use ProphecyTrait;

    private LocateShortUrlVisit $locateVisit;
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
        $this->dbUpdater = $this->prophesize(GeolocationDbUpdaterInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->locateVisit = new LocateShortUrlVisit(
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
        $event = new ShortUrlVisited('123');
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
    public function invalidAddressLogsWarning(): void
    {
        $event = new ShortUrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn(
            new Visit(new ShortUrl(''), new Visitor('', '', '1.2.3.4')),
        );
        $resolveLocation = $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->willThrow(
            WrongIpException::class,
        );
        $logWarning = $this->logger->warning(
            Argument::containingString('Tried to locate visit with id "{visitId}", but its address seems to be wrong.'),
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

    /**
     * @test
     * @dataProvider provideNonLocatableVisits
     */
    public function nonLocatableVisitsResolveToEmptyLocations(Visit $visit): void
    {
        $event = new ShortUrlVisited('123');
        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation(Argument::any());
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), new VisitLocation(Location::emptyInstance()));
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
        $resolveIp->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    public function provideNonLocatableVisits(): iterable
    {
        $shortUrl = new ShortUrl('');

        yield 'null IP' => [new Visit($shortUrl, new Visitor('', '', null))];
        yield 'empty IP' => [new Visit($shortUrl, new Visitor('', '', ''))];
        yield 'localhost' => [new Visit($shortUrl, new Visitor('', '', IpAddress::LOCALHOST))];
    }

    /**
     * @test
     * @dataProvider provideIpAddresses
     */
    public function locatableVisitsResolveToLocation(string $anonymizedIpAddress, ?string $originalIpAddress): void
    {
        $ipAddr = $originalIpAddress ?? $anonymizedIpAddress;
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', $ipAddr));
        $location = new Location('', '', '', '', 0.0, 0.0, '');
        $event = new ShortUrlVisited('123', $originalIpAddress);

        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation($ipAddr)->willReturn($location);
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), new VisitLocation($location));
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
        $resolveIp->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    public function provideIpAddresses(): iterable
    {
        yield 'no original IP address' => ['1.2.3.0', null];
        yield 'original IP address' => ['1.2.3.0', '1.2.3.4'];
    }

    /** @test */
    public function errorWhenUpdatingGeoLiteWithExistingCopyLogsWarning(): void
    {
        $e = GeolocationDbUpdateFailedException::create(true);
        $ipAddr = '1.2.3.0';
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', $ipAddr));
        $location = new Location('', '', '', '', 0.0, 0.0, '');
        $event = new ShortUrlVisited('123');

        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation($ipAddr)->willReturn($location);
        $checkUpdateDb = $this->dbUpdater->checkDbUpdate(Argument::cetera())->willThrow($e);
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertEquals($visit->getVisitLocation(), new VisitLocation($location));
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
        $resolveIp->shouldHaveBeenCalledOnce();
        $checkUpdateDb->shouldHaveBeenCalledOnce();
        $this->logger->warning(
            'GeoLite2 database update failed. Proceeding with old version. {e}',
            ['e' => $e],
        )->shouldHaveBeenCalledOnce();
        $dispatch->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function errorWhenDownloadingGeoLiteCancelsLocation(): void
    {
        $e = GeolocationDbUpdateFailedException::create(false);
        $ipAddr = '1.2.3.0';
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', $ipAddr));
        $location = new Location('', '', '', '', 0.0, 0.0, '');
        $event = new ShortUrlVisited('123');

        $findVisit = $this->em->find(Visit::class, '123')->willReturn($visit);
        $flush = $this->em->flush()->will(function (): void {
        });
        $resolveIp = $this->ipLocationResolver->resolveIpLocation($ipAddr)->willReturn($location);
        $checkUpdateDb = $this->dbUpdater->checkDbUpdate(Argument::cetera())->willThrow($e);
        $logError = $this->logger->error(
            'GeoLite2 database download failed. It is not possible to locate visit with id {visitId}. {e}',
            ['e' => $e, 'visitId' => 123],
        );
        $dispatch = $this->eventDispatcher->dispatch(new VisitLocated('123'))->will(function (): void {
        });

        ($this->locateVisit)($event);

        self::assertNull($visit->getVisitLocation());
        $findVisit->shouldHaveBeenCalledOnce();
        $flush->shouldNotHaveBeenCalled();
        $resolveIp->shouldNotHaveBeenCalled();
        $checkUpdateDb->shouldHaveBeenCalledOnce();
        $logError->shouldHaveBeenCalledOnce();
        $dispatch->shouldHaveBeenCalledOnce();
    }
}
