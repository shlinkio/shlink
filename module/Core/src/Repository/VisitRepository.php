<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

class VisitRepository extends EntityRepository implements VisitRepositoryInterface
{
    /**
     * @return iterable|Visit[]
     */
    public function findUnlocatedVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $dql = <<<DQL
SELECT v FROM Shlinkio\Shlink\Core\Entity\Visit AS v WHERE v.visitLocation IS NULL
DQL;
        $query = $this->getEntityManager()->createQuery($dql);
        $remainingVisitsToProcess = $this->count(['visitLocation' => null]);
        $offset = 0;

        while ($remainingVisitsToProcess > 0) {
            $iterator = $query->setMaxResults($blockSize)
                              ->setFirstResult($offset)
                              ->iterate();

            foreach ($iterator as $key => [$value]) {
                yield $key => $value;
            }

            $remainingVisitsToProcess -= $blockSize;
            $offset += $blockSize;
        }
    }

    /**
     * @return Visit[]
     */
    public function findVisitsByShortCode(
        string $shortCode,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $dateRange);
        $qb->select('v');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function countVisitsByShortCode(string $shortCode, ?DateRange $dateRange = null): int
    {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $dateRange);
        $qb->select('COUNT(DISTINCT v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByShortCodeQueryBuilder(string $shortCode, ?DateRange $dateRange = null): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 'su')
           ->where($qb->expr()->eq('su.shortCode', ':shortCode'))
           ->setParameter('shortCode', $shortCode)
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

        return $qb;
    }
}
