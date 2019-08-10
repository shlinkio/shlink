<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitService implements VisitServiceInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function locateUnlocatedVisits(callable $geolocateVisit, ?callable $notifyVisitWithLocation = null): void
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        $results = $repo->findUnlocatedVisits(false);
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

    private function locateVisit(Visit $visit, VisitLocation $location, ?callable $notifyVisitWithLocation): void
    {
        $visit->locate($location);
        $this->em->persist($visit);

        if ($notifyVisitWithLocation !== null) {
            $notifyVisitWithLocation($location, $visit);
        }
    }
}
