<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

class VisitRepository extends EntityRepository implements VisitRepositoryInterface
{
    private const DEFAULT_BLOCK_SIZE = 10000;

    /**
     * This method will allow you to iterate the whole list of unlocated visits, but loading them into memory in
     * smaller blocks of a specific size.
     * This will have side effects if you update those rows while you iterate them, in a way that they are no longer
     * unlocated.
     * If you plan to do so, pass the first argument as false in order to disable applying offsets while slicing the
     * dataset.
     *
     * @return iterable|Visit[]
     */
    public function findUnlocatedVisits(bool $applyOffset = true): iterable
    {
        $dql = <<<DQL
            SELECT v FROM Shlinkio\Shlink\Core\Entity\Visit AS v WHERE v.visitLocation IS NULL
        DQL;
        $query = $this->getEntityManager()->createQuery($dql);
        $remainingVisitsToProcess = $this->count(['visitLocation' => null]);

        return $this->findVisitsForQuery($query, $remainingVisitsToProcess, $applyOffset);
    }

    /**
     * This method will allow you to iterate the whole list of unlocated visits, but loading them into memory in
     * smaller blocks of a specific size.
     * This will have side effects if you update those rows while you iterate them, in a way that they are no longer
     * unlocated.
     * If you plan to do so, pass the first argument as false in order to disable applying offsets while slicing the
     * dataset.
     *
     * @return iterable|Visit[]
     */
    public function findVisitsWithEmptyLocation(bool $applyOffset = true): iterable
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.visitLocation', 'vl')
           ->where($qb->expr()->isNotNull('v.visitLocation'))
           ->andWhere($qb->expr()->eq('vl.isEmpty', ':isEmpty'))
           ->setParameter('isEmpty', true);
        $countQb = clone $qb;

        $query = $qb->select('v')->getQuery();
        $remainingVisitsToProcess = (int) $countQb->select('COUNT(DISTINCT v.id)')->getQuery()->getSingleScalarResult();

        return $this->findVisitsForQuery($query, $remainingVisitsToProcess, $applyOffset);
    }

    private function findVisitsForQuery(Query $query, int $remainingVisitsToProcess, bool $applyOffset = true): iterable
    {
        $blockSize = self::DEFAULT_BLOCK_SIZE;
        $query = $query->setMaxResults($blockSize);
        $offset = 0;

        // FIXME Do not use the $applyOffset workaround. Instead, always start with first result, but skip already
        //       processed results. That should work both if any entry is edited or not
        while ($remainingVisitsToProcess > 0) {
            $iterator = $query->setFirstResult($applyOffset ? $offset : null)->iterate();
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
        ?string $domain = null,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange);
        $qb->select('v')
           ->orderBy('v.date', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function countVisitsByShortCode(string $shortCode, ?string $domain = null, ?DateRange $dateRange = null): int
    {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange);
        $qb->select('COUNT(DISTINCT v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByShortCodeQueryBuilder(
        string $shortCode,
        ?string $domain,
        ?DateRange $dateRange
    ): QueryBuilder {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 'su')
           ->where($qb->expr()->eq('su.shortCode', ':shortCode'))
           ->setParameter('shortCode', $shortCode);

        // Apply domain filtering
        if ($domain !== null) {
            $qb->join('su.domain', 'd')
               ->andWhere($qb->expr()->eq('d.authority', ':domain'))
               ->setParameter('domain', $domain);
        } else {
            $qb->andWhere($qb->expr()->isNull('su.domain'));
        }

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
