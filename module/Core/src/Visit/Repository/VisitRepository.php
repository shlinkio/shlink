<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfNonOrphanVisits;
use Shlinkio\Shlink\Core\Visit\Spec\CountOfOrphanVisits;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use const PHP_INT_MAX;

/** @extends EntitySpecificationRepository<Visit> */
class VisitRepository extends EntitySpecificationRepository implements VisitRepositoryInterface
{
    /**
     * @return Visit[]
     */
    public function findVisitsByShortCode(ShortUrlIdentifier $identifier, VisitsListFiltering $filtering): array
    {
        $qb = $this->createVisitsByShortCodeQueryBuilder($identifier, $filtering);
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit, $filtering->offset);
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
        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $shortUrlId = $shortUrlRepo->findOne($identifier, $filtering->apiKey?->spec())?->getId() ?? '-1';

        // Parameters in this query need to be part of the query itself, as we need to use it as sub-query later
        // Since they are not provided by the caller, it's reasonably safe
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->where($qb->expr()->eq('v.shortUrl', $shortUrlId));

        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        // Apply date range filtering
        $this->applyDatesInline($qb, $filtering->dateRange);

        return $qb;
    }

    public function findVisitsByTag(string $tag, WithDomainVisitsListFiltering $filtering): array
    {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $filtering);
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit, $filtering->offset);
    }

    public function countVisitsByTag(string $tag, WithDomainVisitsCountFiltering $filtering): int
    {
        $qb = $this->createVisitsByTagQueryBuilder($tag, $filtering);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByTagQueryBuilder(string $tag, WithDomainVisitsCountFiltering $filtering): QueryBuilder
    {
        $conn = $this->getEntityManager()->getConnection();

        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 's')
           ->join('s.tags', 't')
           ->where($qb->expr()->eq('t.name', $conn->quote($tag)));

        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        $domain = $filtering->domain;
        if ($domain === Domain::DEFAULT_AUTHORITY) {
            $qb->andWhere($qb->expr()->isNull('s.domain'));
        } elseif ($domain !== null) {
            $qb->join('s.domain', 'd')
               ->andWhere($qb->expr()->eq('d.authority', $conn->quote($domain)));
        }

        $this->applyDatesInline($qb, $filtering->dateRange);
        $this->applySpecification($qb, $filtering->apiKey?->inlinedSpec(), 'v');

        return $qb;
    }

    /**
     * @return Visit[]
     */
    public function findVisitsByDomain(string $domain, VisitsListFiltering $filtering): array
    {
        $qb = $this->createVisitsByDomainQueryBuilder($domain, $filtering);
        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit, $filtering->offset);
    }

    public function countVisitsByDomain(string $domain, VisitsCountFiltering $filtering): int
    {
        $qb = $this->createVisitsByDomainQueryBuilder($domain, $filtering);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByDomainQueryBuilder(string $domain, VisitsCountFiltering $filtering): QueryBuilder
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->join('v.shortUrl', 's');

        if ($domain === Domain::DEFAULT_AUTHORITY) {
            $qb->where($qb->expr()->isNull('s.domain'));
        } else {
            $qb->join('s.domain', 'd')
               ->where($qb->expr()->eq('d.authority', $this->getEntityManager()->getConnection()->quote($domain)));
        }

        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        $this->applyDatesInline($qb, $filtering->dateRange);
        $this->applySpecification($qb, $filtering->apiKey?->inlinedSpec(), 'v');

        return $qb;
    }

    public function findOrphanVisits(OrphanVisitsListFiltering $filtering): array
    {
        if ($filtering->apiKey?->hasRole(Role::NO_ORPHAN_VISITS)) {
            return [];
        }

        $qb = $this->createAllVisitsQueryBuilder($filtering);
        $qb->andWhere($qb->expr()->isNull('v.shortUrl'));

        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        if ($filtering->type) {
            $conn = $this->getEntityManager()->getConnection();
            $qb->andWhere($qb->expr()->eq('v.type', $conn->quote($filtering->type->value)));
        }

        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit, $filtering->offset);
    }

    public function countOrphanVisits(OrphanVisitsCountFiltering $filtering): int
    {
        if ($filtering->apiKey?->hasRole(Role::NO_ORPHAN_VISITS)) {
            return 0;
        }

        return (int) $this->matchSingleScalarResult(new CountOfOrphanVisits($filtering));
    }

    /**
     * @return Visit[]
     */
    public function findNonOrphanVisits(WithDomainVisitsListFiltering $filtering): array
    {
        $qb = $this->createAllVisitsQueryBuilder($filtering);
        $qb->andWhere($qb->expr()->isNotNull('v.shortUrl'));

        $apiKey = $filtering->apiKey;
        if (ApiKey::isShortUrlRestricted($apiKey)) {
            $qb->join('v.shortUrl', 's');
        }

        $this->applySpecification($qb, $apiKey?->inlinedSpec(), 'v');

        return $this->resolveVisitsWithNativeQuery($qb, $filtering->limit, $filtering->offset);
    }

    public function countNonOrphanVisits(VisitsCountFiltering $filtering): int
    {
        return (int) $this->matchSingleScalarResult(new CountOfNonOrphanVisits($filtering));
    }

    private function createAllVisitsQueryBuilder(VisitsCountFiltering $filtering): QueryBuilder
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later.
        // Since they are not provided by the caller, it's reasonably safe.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v');

        if ($filtering->excludeBots) {
            $qb->andWhere($qb->expr()->eq('v.potentialBot', 'false'));
        }

        $this->applyDatesInline($qb, $filtering->dateRange);

        return $qb;
    }

    private function applyDatesInline(QueryBuilder $qb, DateRange|null $dateRange): void
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($dateRange?->startDate !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', $conn->quote($dateRange->startDate->toDateTimeString())));
        }
        if ($dateRange?->endDate !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', $conn->quote($dateRange->endDate->toDateTimeString())));
        }
    }

    private function resolveVisitsWithNativeQuery(QueryBuilder $qb, int|null $limit, int|null $offset): array
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
                 // @phpstan-ignore-next-line
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

    public function findMostRecentOrphanVisit(): Visit|null
    {
        $dql = <<<DQL
            SELECT v
              FROM Shlinkio\Shlink\Core\Visit\Entity\Visit AS v
             WHERE v.shortUrl IS NULL
          ORDER BY v.id DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }
}
