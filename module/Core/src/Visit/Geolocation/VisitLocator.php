<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Geolocation;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Repository\VisitLocationRepositoryInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitLocator implements VisitLocatorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VisitLocationRepositoryInterface $repo,
    ) {
    }

    public function locateUnlocatedVisits(VisitGeolocationHelperInterface $helper): void
    {
        $this->locateVisits($this->repo->findUnlocatedVisits(), $helper);
    }

    public function locateVisitsWithEmptyLocation(VisitGeolocationHelperInterface $helper): void
    {
        $this->locateVisits($this->repo->findVisitsWithEmptyLocation(), $helper);
    }

    public function locateAllVisits(VisitGeolocationHelperInterface $helper): void
    {
        $this->locateVisits($this->repo->findAllVisits(), $helper);
    }

    /**
     * @param iterable|Visit[] $results
     */
    private function locateVisits(iterable $results, VisitGeolocationHelperInterface $helper): void
    {
        $count = 0;
        $persistBlock = 200;

        foreach ($results as $visit) {
            $count++;

            try {
                $location = $helper->geolocateVisit($visit);
            } catch (IpCannotBeLocatedException $e) {
                if (! $e->isNonLocatableAddress()) {
                    // Skip if the visit's IP could not be located because of an error
                    continue;
                }

                // If the IP address is non-locatable, locate it as empty to prevent next processes to pick it again
                $location = Location::emptyInstance();
            }

            $this->locateVisit($visit, VisitLocation::fromGeolocation($location), $helper);

            // Flush and clear after X iterations
            if ($count % $persistBlock === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function locateVisit(Visit $visit, VisitLocation $location, VisitGeolocationHelperInterface $helper): void
    {
        $prevLocation = $visit->getVisitLocation();

        $visit->locate($location);
        $this->em->persist($visit);

        // In order to avoid leaving orphan locations, remove the previous one
        if ($prevLocation !== null) {
            $this->em->remove($prevLocation);
        }

        $helper->onVisitLocated($location, $visit);
    }
}
