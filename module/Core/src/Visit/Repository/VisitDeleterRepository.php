<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

/** @extends EntitySpecificationRepository<Visit> */
class VisitDeleterRepository extends EntitySpecificationRepository implements VisitDeleterRepositoryInterface
{
    public function deleteShortUrlVisits(ShortUrl $shortUrl): int
    {
        return $this->getEntityManager()->wrapInTransaction(function () use ($shortUrl): int {
            $this->deleteByShortUrl(ShortUrlVisitsCount::class, $shortUrl);
            return $this->deleteByShortUrl(Visit::class, $shortUrl);
        });
    }

    /**
     * @param class-string<Visit | ShortUrlVisitsCount> $entityName
     */
    private function deleteByShortUrl(string $entityName, ShortUrl $shortUrl): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($entityName, 'v')
           ->where($qb->expr()->eq('v.shortUrl', ':shortUrl'))
           ->setParameter('shortUrl', $shortUrl);

        return $qb->getQuery()->execute();
    }

    public function deleteOrphanVisits(): int
    {
        $em = $this->getEntityManager();
        return $em->wrapInTransaction(function () use ($em): int {
            $em->createQueryBuilder()->delete(OrphanVisitsCount::class, 'v')->getQuery()->execute();

            $qb = $em->createQueryBuilder();
            $qb->delete(Visit::class, 'v')
               ->where($qb->expr()->isNull('v.shortUrl'));

            return $qb->getQuery()->execute();
        });
    }
}
