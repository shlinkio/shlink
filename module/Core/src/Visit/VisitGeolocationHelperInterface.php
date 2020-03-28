<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

interface VisitGeolocationHelperInterface
{
    /**
     * @throws IpCannotBeLocatedException
     */
    public function geolocateVisit(Visit $visit): Location;

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void;
}
