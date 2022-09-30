<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Geolocation;

use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

interface VisitGeolocationHelperInterface
{
    /**
     * @throws IpCannotBeLocatedException
     */
    public function geolocateVisit(Visit $visit): Location;

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void;
}
