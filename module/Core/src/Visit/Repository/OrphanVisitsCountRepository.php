<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class OrphanVisitsCountRepository extends EntitySpecificationRepository implements OrphanVisitsCountRepositoryInterface
{
    public function countOrphanVisits(VisitsCountFiltering $filtering): int
    {
        if ($filtering->apiKey?->hasRole(Role::NO_ORPHAN_VISITS)) {
            return 0;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COALESCE(SUM(vc.count), 0)')
           ->from(OrphanVisitsCount::class, 'vc');

        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('vc.potentialBot', ':potentialBot'))
               ->setParameter('potentialBot', false);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
