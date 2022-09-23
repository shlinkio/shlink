<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\EventDispatcher\LocateUnlocatedVisits;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocatorInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class LocateUnlocatedVisitsTest extends TestCase
{
    use ProphecyTrait;

    private LocateUnlocatedVisits $listener;
    private ObjectProphecy $locator;
    private ObjectProphecy $visitToLocation;

    protected function setUp(): void
    {
        $this->locator = $this->prophesize(VisitLocatorInterface::class);
        $this->visitToLocation = $this->prophesize(VisitToLocationHelperInterface::class);

        $this->listener = new LocateUnlocatedVisits($this->locator->reveal(), $this->visitToLocation->reveal());
    }

    /** @test */
    public function locatorIsCalledWhenInvoked(): void
    {
        ($this->listener)(new GeoLiteDbCreated());
        $this->locator->locateUnlocatedVisits($this->listener)->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function visitToLocationHelperIsCalledToGeolocateVisits(): void
    {
        $visit = Visit::forBasePath(Visitor::emptyInstance());
        $location = Location::emptyInstance();

        $resolve = $this->visitToLocation->resolveVisitLocation($visit)->willReturn($location);

        $result = $this->listener->geolocateVisit($visit);

        self::assertSame($location, $result);
        $resolve->shouldHaveBeenCalledOnce();
    }
}
