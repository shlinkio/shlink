<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocatorInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class LocateUnlocatedVisits implements VisitGeolocationHelperInterface
{
    public function __construct(
        private readonly VisitLocatorInterface $locator,
        private readonly VisitToLocationHelperInterface $visitToLocation,
    ) {
    }

    public function __invoke(GeoLiteDbCreated $event): void
    {
        $this->locator->locateUnlocatedVisits($this);
    }

    /**
     * @throws IpCannotBeLocatedException
     */
    public function geolocateVisit(Visit $visit): Location
    {
        return $this->visitToLocation->resolveVisitLocation($visit);
    }

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
    {
    }
}
