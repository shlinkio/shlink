<?php
namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Shlinkio\Shlink\Core\Entity\Visit;

class VisitRepository extends EntityRepository implements VisitRepositoryInterface
{
    /**
     * @return Visit[]
     */
    public function findUnlocatedVisits()
    {
        $qb = $this->createQueryBuilder('v');
        $qb->where($qb->expr()->isNull('v.visitLocation'));

        return $qb->getQuery()->getResult();
    }
}
