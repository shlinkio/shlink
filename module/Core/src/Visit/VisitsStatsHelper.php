<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;

class VisitsStatsHelper implements VisitsStatsHelperInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getVisitsStats(): VisitsStats
    {
        return new VisitsStats($this->getVisitsCount());
    }

    private function getVisitsCount(): int
    {
        /** @var VisitRepository $visitsRepo */
        $visitsRepo = $this->em->getRepository(Visit::class);
        return $visitsRepo->count([]);
    }
}
