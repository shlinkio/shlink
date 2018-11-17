<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;

class VisitRepository extends EntityRepository implements VisitRepositoryInterface
{
    public function findUnlocatedVisits(): iterable
    {
        $qb = $this->createQueryBuilder('v');
        $qb->where($qb->expr()->isNull('v.visitLocation'));

        return $qb->getQuery()->iterate();
    }

    /**
     * @param ShortUrl|int $shortUrlOrId
     * @param DateRange|null $dateRange
     * @return Visit[]
     */
    public function findVisitsByShortUrl($shortUrlOrId, DateRange $dateRange = null): array
    {
        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $shortUrlOrId instanceof ShortUrl
            ? $shortUrlOrId
            : $this->getEntityManager()->find(ShortUrl::class, $shortUrlOrId);

        if ($shortUrl === null) {
            return [];
        }

        $qb = $this->createQueryBuilder('v');
        $qb->where($qb->expr()->eq('v.shortUrl', ':shortUrl'))
           ->setParameter('shortUrl', $shortUrl)
           ->orderBy('v.date', 'DESC') ;

        // Apply date range filtering
        if ($dateRange !== null && $dateRange->getStartDate() !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', ':startDate'))
               ->setParameter('startDate', $dateRange->getStartDate());
        }
        if ($dateRange !== null && $dateRange->getEndDate() !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', ':endDate'))
               ->setParameter('endDate', $dateRange->getEndDate());
        }

        return $qb->getQuery()->getResult();
    }
}
