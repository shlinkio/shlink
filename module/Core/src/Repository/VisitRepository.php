<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfOrphanVisits;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfShortUrlVisits;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('v')
           ->from(Visit::class, 'v');

        return $this->visitsIterableForQuery($qb, $blockSize);
    }

    private function visitsIterableForQuery(QueryBuilder $qb, int $blockSize): iterable
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
        ?int $offset = null,
        ?Specification $spec = null
    ): array {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange, $spec);
        return $this->resolveVisitsWithNativeQuery($qb, $limit, $offset);
    }

    public function countVisitsByShortCode(
        string $shortCode,
        ?string $domain = null,
        ?DateRange $dateRange = null,
        ?Specification $spec = null
    ): int {
        $qb = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange, $spec);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByShortCodeQueryBuilder(
        string $shortCode,
        ?string $domain,
        ?DateRange $dateRange,
        ?Specification $spec = null
    ): QueryBuilder {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOne($shortCode, $domain, $spec);
        $shortUrlId = $shortUrl !== null ? $shortUrl->getId() : -1;

        // Parameters in this query need to be part of the query itself, as we need to use it a sub-query later
        // Since they are not strictly provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->where($qb->expr()->eq('v.shortUrl', $shortUrlId));

        // Apply date range filtering
        $this->applyDatesInline($qb, $dateRange);

        return $qb;
    }

    public function findVisitsByTag(
        string $tag,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null,
        ?Specification $spec = null
    ): array {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $dateRange, $spec);
        return $this->resolveVisitsWithNativeQuery($qb, $limit, $offset);
    }

    public function countVisitsByTag(string $tag, ?DateRange $dateRange = null, ?Specification $spec = null): int
    {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $dateRange, $spec);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByTagQueryBuilder(
        string $tag,
        ?DateRange $dateRange,
        ?Specification $spec
    ): QueryBuilder {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        // Since they are not strictly provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 's')
           ->join('s.tags', 't')
           ->where($qb->expr()->eq('t.name', '\'' . $tag . '\'')); // This needs to be concatenated, not bound

        $this->applyDatesInline($qb, $dateRange);
        $this->applySpecification($qb, $spec, 'v');

        return $qb;
    }

    public function findOrphanVisits(?DateRange $dateRange = null, ?int $limit = null, ?int $offset = null): array
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        // Since they are not strictly provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->where($qb->expr()->isNull('v.shortUrl'));

        $this->applyDatesInline($qb, $dateRange);

        return $this->resolveVisitsWithNativeQuery($qb, $limit, $offset);
    }

    public function countOrphanVisits(?DateRange $dateRange = null): int
    {
        return (int) $this->matchSingleScalarResult(new CountOfOrphanVisits($dateRange));
    }

    public function countVisits(?ApiKey $apiKey = null): int
    {
        return (int) $this->matchSingleScalarResult(new CountOfShortUrlVisits($apiKey));
    }

    private function applyDatesInline(QueryBuilder $qb, ?DateRange $dateRange): void
    {
        if ($dateRange !== null && $dateRange->getStartDate() !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', '\'' . $dateRange->getStartDate()->toDateTimeString() . '\''));
        }
        if ($dateRange !== null && $dateRange->getEndDate() !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', '\'' . $dateRange->getEndDate()->toDateTimeString() . '\''));
        }
    }

    private function resolveVisitsWithNativeQuery(QueryBuilder $qb, ?int $limit, ?int $offset): array
    {
        // TODO Order by date and ID, not just by ID (order by date DESC, id DESC).
        //      That ensures imported visits are properly ordered even if inserted in wrong chronological order.

        $qb->select('v.id')
           ->orderBy('v.id', 'DESC')
           // Falling back to values that will behave as no limit/offset, but will workaround MS SQL not allowing
           // order on sub-queries without offset
           ->setMaxResults($limit ?? PHP_INT_MAX)
           ->setFirstResult($offset ?? 0);
        $subQuery = $qb->getQuery()->getSQL();

        // A native query builder needs to be used here because DQL and ORM query builders do not accept
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

        $query = $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm);

        return $query->getResult();
    }
}
