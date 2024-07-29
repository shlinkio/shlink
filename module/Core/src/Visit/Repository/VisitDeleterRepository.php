<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

/** @extends EntitySpecificationRepository<Visit> */
class VisitDeleterRepository extends EntitySpecificationRepository implements VisitDeleterRepositoryInterface
{
    public function deleteShortUrlVisits(ShortUrl $shortUrl): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Visit::class, 'v')
           ->where($qb->expr()->eq('v.shortUrl', ':shortUrl'))
           ->setParameter('shortUrl', $shortUrl);

        return $qb->getQuery()->execute();
    }

    public function deleteOrphanVisits(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Visit::class, 'v')
           ->where($qb->expr()->isNull('v.shortUrl'));

        return $qb->getQuery()->execute();
    }
}
