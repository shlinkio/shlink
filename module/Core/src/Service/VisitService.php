<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
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

    /**
     * @return Visit[]
     */
    public function getUnlocatedVisits(): array
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        return $repo->findUnlocatedVisits();
    }

    public function locateVisit(Visit $visit, VisitLocation $location, bool $clear = false): void
    {
        $visit->locate($location);

        $this->em->persist($visit);
        $this->em->flush();

        if ($clear) {
            $this->em->clear(VisitLocation::class);
            $this->em->clear(Visit::class);
        }
    }
}
