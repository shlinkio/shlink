<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitLocator implements VisitLocatorInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function locateUnlocatedVisits(callable $geolocateVisit, callable $notifyVisitWithLocation): void
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        $this->locateVisits($repo->findUnlocatedVisits(false), $geolocateVisit, $notifyVisitWithLocation);
    }

    public function locateVisitsWithEmptyLocation(callable $geolocateVisit, callable $notifyVisitWithLocation): void
    {
        $this->locateVisits([], $geolocateVisit, $notifyVisitWithLocation);
    }

    /**
     * @param iterable|Visit[] $results
     */
    private function locateVisits(iterable $results, callable $geolocateVisit, callable $notifyVisitWithLocation): void
    {
        $count = 0;
        $persistBlock = 200;

        foreach ($results as $visit) {
            $count++;

            try {
                /** @var Location $location */
                $location = $geolocateVisit($visit);
            } catch (IpCannotBeLocatedException $e) {
                if (! $e->isNonLocatableAddress()) {
                    // Skip if the visit's IP could not be located because of an error
                    continue;
                }

                // If the IP address is non-locatable, locate it as empty to prevent next processes to pick it again
                $location = Location::emptyInstance();
            }

            $location = new VisitLocation($location);
            $this->locateVisit($visit, $location, $notifyVisitWithLocation);

            // Flush and clear after X iterations
            if ($count % $persistBlock === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function locateVisit(Visit $visit, VisitLocation $location, callable $notifyVisitWithLocation): void
    {
        $visit->locate($location);
        $this->em->persist($visit);

        $notifyVisitWithLocation($location, $visit);
    }
}
