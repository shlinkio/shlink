<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

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
        $results = $repo->findUnlocatedVisits();

        foreach ($results as [$visit]) {
            try {
                /** @var Location $location */
                $location = $geolocateVisit($visit);
            } catch (IpCannotBeLocatedException $e) {
                // Skip if the visit's IP could not be located
                continue;
            }

            $location = new VisitLocation($location);
            $this->locateVisit($visit, $location, $notifyVisitWithLocation);
        }
    }

    private function locateVisit(Visit $visit, VisitLocation $location, ?callable $notifyVisitWithLocation): void
    {
        $visit->locate($location);

        $this->em->persist($visit);
        $this->em->flush();

        if ($notifyVisitWithLocation !== null) {
            $notifyVisitWithLocation($location, $visit);
        }

        $this->em->clear();
    }
}
