<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

/**
 * Allows iterating large amounts of visits in a memory-efficient way, to use in batch processes
 * @extends EntitySpecificationRepository<Visit>
 */
class VisitIterationRepository extends EntitySpecificationRepository implements VisitIterationRepositoryInterface
{
    /**
     * @return iterable<Visit>
     */
    public function findUnlocatedVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('v')
           ->from(Visit::class, 'v')
           ->where($qb->expr()->isNull('v.visitLocation'));

        return $this->visitsIterableForQuery($qb, $blockSize);
    }

    /**
     * @return iterable<Visit>
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

        return $this->visitsIterableForQuery($qb, $blockSize);
    }

    /**
     * @return iterable<Visit>
     */
    public function findAllVisits(DateRange|null $dateRange = null, int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $qb = $this->createQueryBuilder('v');
        if ($dateRange?->startDate !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', ':since'))
               ->setParameter('since', $dateRange->startDate, ChronosDateTimeType::CHRONOS_DATETIME);
        }
        if ($dateRange?->endDate !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', ':until'))
               ->setParameter('until', $dateRange->endDate, ChronosDateTimeType::CHRONOS_DATETIME);
        }

        return $this->visitsIterableForQuery($qb, $blockSize);
    }

    private function visitsIterableForQuery(QueryBuilder $qb, int $blockSize): iterable
    {
        $originalQueryBuilder = $qb->setMaxResults($blockSize)
                                   ->orderBy('v.id', 'ASC');
        $lastId = '0';

        do {
            $qb = (clone $originalQueryBuilder)->andWhere($qb->expr()->gt('v.id', $lastId));
            $iterator = $qb->getQuery()->toIterable();
            $resultsFound = false;
            /** @var Visit|null $lastProcessedVisit */
            $lastProcessedVisit = null;

            foreach ($iterator as $key => $visit) {
                $resultsFound = true;
                $lastProcessedVisit = $visit;
                yield $key => $visit;
            }

            // As the query is ordered by ID, we can take the last one every time in order to exclude the whole list
            $lastId = $lastProcessedVisit?->getId() ?? $lastId;
        } while ($resultsFound);
    }
}
