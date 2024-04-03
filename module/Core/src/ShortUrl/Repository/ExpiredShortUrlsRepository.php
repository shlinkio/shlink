<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;

use function sprintf;

class ExpiredShortUrlsRepository extends EntitySpecificationRepository implements ExpiredShortUrlsRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function delete(ExpiredShortUrlsConditions $conditions = new ExpiredShortUrlsConditions()): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(ShortUrl::class, 's');

        return $this->applyConditions($qb, $conditions, fn () => (int) $qb->getQuery()->execute());
    }

    /**
     * @inheritDoc
     */
    public function dryCount(ExpiredShortUrlsConditions $conditions = new ExpiredShortUrlsConditions()): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(s.id)')
           ->from(ShortUrl::class, 's');

        return $this->applyConditions($qb, $conditions, fn () => (int) $qb->getQuery()->getSingleScalarResult());
    }

    /**
     * @param callable(): int $getResultFromQueryBuilder
     */
    private function applyConditions(
        QueryBuilder $qb,
        ExpiredShortUrlsConditions $conditions,
        callable $getResultFromQueryBuilder,
    ): int {
        if (! $conditions->hasConditions()) {
            return 0;
        }

        if ($conditions->pastValidUntil) {
            $qb
                ->where($qb->expr()->andX(
                    $qb->expr()->isNotNull('s.validUntil'),
                    $qb->expr()->lt('s.validUntil', ':now'),
                ))
                ->setParameter('now', Chronos::now()->toDateTimeString());
        }

        if ($conditions->maxVisitsReached) {
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->isNotNull('s.maxVisits'),
                $qb->expr()->lte(
                    's.maxVisits',
                    sprintf(
                        '(SELECT COALESCE(SUM(vc.count), 0) FROM %s as vc WHERE vc.shortUrl=s)',
                        ShortUrlVisitsCount::class,
                    ),
                ),
            ));
        }

        return $getResultFromQueryBuilder();
    }
}
