<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Geolocation;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

interface VisitToLocationHelperInterface
{
    /**
     * @throws IpCannotBeLocatedException
     */
    public function resolveVisitLocation(Visit $visit): Location;
}
