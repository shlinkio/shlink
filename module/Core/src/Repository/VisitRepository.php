<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
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

    /**
     * @param ShortUrl|int $shortUrl
     * @param DateRange|null $dateRange
     * @return Visit[]
     */
    public function findVisitsByShortUrl($shortUrl, DateRange $dateRange = null)
    {
        $shortUrl = $shortUrl instanceof ShortUrl
            ? $shortUrl
            : $this->getEntityManager()->find(ShortUrl::class, $shortUrl);

        $qb = $this->createQueryBuilder('v');
        $qb->where($qb->expr()->eq('v.shortUrl', ':shortUrl'))
           ->setParameter('shortUrl', $shortUrl)
           ->orderBy('v.date', 'DESC') ;

        // Apply date range filtering
        if (! empty($dateRange->getStartDate())) {
            $qb->andWhere($qb->expr()->gte('v.date', ':startDate'))
               ->setParameter('startDate', $dateRange->getStartDate());
        }
        if (! empty($dateRange->getEndDate())) {
            $qb->andWhere($qb->expr()->lte('v.date', ':endDate'))
               ->setParameter('endDate', $dateRange->getEndDate());
        }

        return $qb->getQuery()->getResult();
    }
}
