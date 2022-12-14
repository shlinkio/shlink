<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

class VisitLocationRepository extends EntitySpecificationRepository implements VisitLocationRepositoryInterface
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
    public function findAllVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable
    {
        $qb = $this->createQueryBuilder('v');
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
