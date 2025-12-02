<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\EventDispatcher\LocateUnlocatedVisits;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocatorInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class LocateUnlocatedVisitsTest extends TestCase
{
    private LocateUnlocatedVisits $listener;
    private MockObject & VisitLocatorInterface $locator;
    private MockObject & VisitToLocationHelperInterface $visitToLocation;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(VisitLocatorInterface::class);
        $this->visitToLocation = $this->createMock(VisitToLocationHelperInterface::class);

        $this->listener = new LocateUnlocatedVisits($this->locator, $this->visitToLocation);
    }

    #[Test]
    public function locatorIsCalledWhenInvoked(): void
    {
        $this->locator->expects($this->once())->method('locateUnlocatedVisits')->with($this->listener);
        ($this->listener)(new GeoLiteDbCreated());
    }

    #[Test]
    public function visitToLocationHelperIsCalledToGeolocateVisits(): void
    {
        $visit = Visit::forBasePath(Visitor::empty());
        $location = Location::empty();

        $this->visitToLocation->expects($this->once())->method('resolveVisitLocation')->with($visit)->willReturn(
            $location,
        );

        $result = $this->listener->geolocateVisit($visit);

        self::assertSame($location, $result);
    }
}
