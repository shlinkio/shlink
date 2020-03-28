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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('v')
           ->from(Visit::class, 'v')
           ->where($qb->expr()->isNull('v.visitLocation'));

        return $this->findVisitsForQuery($qb, $blockSize);
    }

    /**
     * @return iterable|Visit[]
     */
    public function findVisitsWithEmptyLocation(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('v')
           ->from(Visit::class, 'v')
           ->join('v.visitLocation', 'vl')
           ->where($qb->expr()->isNotNull('v.visitLocation'))
           ->andWhere($qb->expr()->eq('vl.isEmpty', ':isEmpty'))
           ->setParameter('isEmpty', true);

        return $this->findVisitsForQuery($qb, $blockSize);
    }

    public function findAllVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('v')
           ->from(Visit::class, 'v');

        return $this->findVisitsForQuery($qb, $blockSize);
    }

    private function findVisitsForQuery(QueryBuilder $qb, int $blockSize): iterable
    {
        $originalQueryBuilder = $qb->setMaxResults($blockSize)
                                   ->orderBy('v.id', 'ASC');
        $lastId = '0';

        do {
            $qb = (clone $originalQueryBuilder)->andWhere($qb->expr()->gt('v.id', $lastId));
            $iterator = $qb->getQuery()->iterate();
            $resultsFound = false;

            /** @var Visit $visit */
            foreach ($iterator as $key => [$visit]) {
                $resultsFound = true;
                yield $key => $visit;
            }

            // As the query is ordered by ID, we can take the last one every time in order to exclude the whole list
            $lastId = isset($visit) ? $visit->getId() : $lastId;
        } while ($resultsFound);
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
