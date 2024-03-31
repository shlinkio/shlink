<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;

class ShortUrlVisitsCountRepository extends EntitySpecificationRepository implements
    ShortUrlVisitsCountRepositoryInterface
{
    public function countNonOrphanVisits(VisitsCountFiltering $filtering): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COALESCE(SUM(vc.count), 0)')
           ->from(ShortUrlVisitsCount::class, 'vc')
           ->join('vc.shortUrl', 's');


        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('vc.potentialBot', ':potentialBot'))
               ->setParameter('potentialBot', false);
        }

        $this->applySpecification($qb, $filtering->apiKey?->spec(), 's');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
