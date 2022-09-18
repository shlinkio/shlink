<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\VisitLocatorInterface;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class LocateUnlocatedVisits implements VisitGeolocationHelperInterface
{
    public function __construct(
        private readonly VisitLocatorInterface $locator,
        private readonly IpLocationResolverInterface $ipLocationResolver,
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
        // TODO This method duplicates code from LocateVisitsCommand. Move to a common place.
        if (! $visit->hasRemoteAddr()) {
            throw IpCannotBeLocatedException::forEmptyAddress();
        }

        $ipAddr = $visit->getRemoteAddr() ?? '';
        if ($ipAddr === IpAddress::LOCALHOST) {
            throw IpCannotBeLocatedException::forLocalhost();
        }

        try {
            return $this->ipLocationResolver->resolveIpLocation($ipAddr);
        } catch (WrongIpException $e) {
            throw IpCannotBeLocatedException::forError($e);
        }
    }

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
    {
        // Do nothing
    }
}
