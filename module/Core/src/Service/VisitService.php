<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitService implements VisitServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function locateVisits(callable $getGeolocationData, ?callable $locatedVisit = null): void
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        $results = $repo->findUnlocatedVisits();

        foreach ($results as [$visit]) {
            try {
                $locationData = $getGeolocationData($visit);
            } catch (IpCannotBeLocatedException $e) {
                // Skip if the visit's IP could not be located
                continue;
            }

            $location = new VisitLocation($locationData);
            $this->locateVisit($visit, $location, $locatedVisit);
        }
    }

    private function locateVisit(Visit $visit, VisitLocation $location, ?callable $locatedVisit): void
    {
        $visit->locate($location);

        $this->em->persist($visit);
        $this->em->flush();

        if ($locatedVisit !== null) {
            $locatedVisit($location, $visit);
        }

        $this->em->clear();
    }
}
