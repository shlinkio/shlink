<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfNonOrphanVisits;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfOrphanVisits;

use const PHP_INT_MAX;

class VisitRepository extends EntitySpecificationRepository implements VisitRepositoryInterface
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

        return $this->visitsIterableForQuery($qb, $blockSize);
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

        return $this->visitsIterableForQuery($qb, $blockSize);
    }

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

    /**
     * @return Visit[]
     */
    public function findVisitsByShortCode(ShortUrlIdentifier $identifier, VisitsListFiltering $filtering): array
    {
        $qb = $this->createVisitsByShortCodeQueryBuilder($identifier, $filtering);
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit(), $filtering->offset());
    }

    public function countVisitsByShortCode(ShortUrlIdentifier $identifier, VisitsCountFiltering $filtering): int
    {
        $qb = $this->createVisitsByShortCodeQueryBuilder($identifier, $filtering);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByShortCodeQueryBuilder(
        ShortUrlIdentifier $identifier,
        VisitsCountFiltering $filtering,
    ): QueryBuilder {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $shortUrlId = $shortUrlRepo->findOne($identifier, $filtering->apiKey()?->spec())?->getId() ?? '-1';

        // Parameters in this query need to be part of the query itself, as we need to use it as sub-query later
        // Since they are not provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->where($qb->expr()->eq('v.shortUrl', $shortUrlId));

        if ($filtering->excludeBots()) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        // Apply date range filtering
        $this->applyDatesInline($qb, $filtering->dateRange());

        return $qb;
    }

    public function findVisitsByTag(string $tag, VisitsListFiltering $filtering): array
    {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $filtering);
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit(), $filtering->offset());
    }

    public function countVisitsByTag(string $tag, VisitsCountFiltering $filtering): int
    {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $filtering);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByTagQueryBuilder(string $tag, VisitsCountFiltering $filtering): QueryBuilder
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 's')
           ->join('s.tags', 't')
           ->where($qb->expr()->eq('t.name', $this->getEntityManager()->getConnection()->quote($tag)));

        if ($filtering->excludeBots()) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        $this->applyDatesInline($qb, $filtering->dateRange());
        $this->applySpecification($qb, $filtering->apiKey()?->inlinedSpec(), 'v');

        return $qb;
    }

    public function findOrphanVisits(VisitsListFiltering $filtering): array
    {
        $qb = $this->createAllVisitsQueryBuilder($filtering);
        $qb->andWhere($qb->expr()->isNull('v.shortUrl'));
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit(), $filtering->offset());
    }

    public function countOrphanVisits(VisitsCountFiltering $filtering): int
    {
        return (int) $this->matchSingleScalarResult(new CountOfOrphanVisits($filtering));
    }

    /**
     * @return Visit[]
     */
    public function findNonOrphanVisits(VisitsListFiltering $filtering): array
    {
        $qb = $this->createAllVisitsQueryBuilder($filtering);
        $qb->andWhere($qb->expr()->isNotNull('v.shortUrl'));

        $this->applySpecification($qb, $filtering->apiKey()?->inlinedSpec());

        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit(), $filtering->offset());
    }

    public function countNonOrphanVisits(VisitsCountFiltering $filtering): int
    {
        return (int) $this->matchSingleScalarResult(new CountOfNonOrphanVisits($filtering));
    }

    private function createAllVisitsQueryBuilder(VisitsListFiltering $filtering): QueryBuilder
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        // Since they are not provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v');

        if ($filtering->excludeBots()) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        $this->applyDatesInline($qb, $filtering->dateRange());

        return $qb;
    }

    private function applyDatesInline(QueryBuilder $qb, ?DateRange $dateRange): void
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($dateRange?->startDate() !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', $conn->quote($dateRange->startDate()->toDateTimeString())));
        }
        if ($dateRange?->endDate() !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', $conn->quote($dateRange->endDate()->toDateTimeString())));
        }
    }

    private function resolveVisitsWithNativeQuery(QueryBuilder $qb, ?int $limit, ?int $offset): array
    {
        // TODO Order by date and ID, not just by ID (order by date DESC, id DESC).
        //      That ensures imported visits are properly ordered even if inserted in wrong chronological order.

        $qb->select('v.id')
           ->orderBy('v.id', 'DESC')
           // Falling back to values that will behave as no limit/offset, but will work around MS SQL not allowing
           // order on sub-queries without offset
           ->setMaxResults($limit ?? PHP_INT_MAX)
           ->setFirstResult($offset ?? 0);
        $subQuery = $qb->getQuery()->getSQL();

        // A native query builder needs to be used here, because DQL and ORM query builders do not support
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, then performance drops dramatically while the "offset" grows.
        $nativeQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $nativeQb->select('v.id AS visit_id', 'v.*', 'vl.*')
                 ->from('visits', 'v')
                 ->join('v', '(' . $subQuery . ')', 'sq', $nativeQb->expr()->eq('sq.id_0', 'v.id'))
                 ->leftJoin('v', 'visit_locations', 'vl', $nativeQb->expr()->eq('v.visit_location_id', 'vl.id'))
                 ->orderBy('v.id', 'DESC');

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Visit::class, 'v', ['id' => 'visit_id']);
        $rsm->addJoinedEntityFromClassMetadata(VisitLocation::class, 'vl', 'v', 'visitLocation', [
            'id' => 'visit_location_id',
        ]);

        return $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm)->getResult();
    }
}
