<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\QueryBuilder;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;

use function preg_replace;

use const PHP_INT_MAX;

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
        /**
         * @var QueryBuilder $qb
         * @var ShortUrl|int $shortUrl
         */
        [$qb, $shortUrl] = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange);
        $qb->select('v.id')
           ->orderBy('v.id', 'DESC')
           // Falling back to values that will behave as no limit/offset, but will workaround MS SQL not allowing
           // order on sub-queries without offset
           ->setMaxResults($limit ?? PHP_INT_MAX)
           ->setFirstResult($offset ?? 0);

        // FIXME Crappy way to resolve the params into the query. Best option would be to inject the sub-query with
        //       placeholders and then pass params to the main query
        $shortUrlId = $shortUrl instanceof ShortUrl ? $shortUrl->getId() : $shortUrl;
        $subQuery = $qb->getQuery()->getSQL();
        $subQuery = preg_replace('/\?/', $shortUrlId, $subQuery, 1);
        if ($dateRange !== null && $dateRange->getStartDate() !== null) {
            $subQuery = preg_replace(
                '/\?/',
                '\'' . $dateRange->getStartDate()->toDateTimeString() . '\'',
                $subQuery,
                1,
            );
        }
        if ($dateRange !== null && $dateRange->getEndDate() !== null) {
            $subQuery = preg_replace('/\?/', '\'' . $dateRange->getEndDate()->toDateTimeString() . '\'', $subQuery, 1);
        }

        // A native query builder needs to be used here because DQL and ORM query builders do not accept
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, then the performance drops dramatically while the "offset" grows.
        $nativeQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $nativeQb->select('v.*', 'vl.*')
                 ->from('visits', 'v')
                 ->join('v', '(' . $subQuery . ')', 'o', $nativeQb->expr()->eq('o.id_0', 'v.id'))
                 ->leftJoin('v', 'visit_locations', 'vl', $nativeQb->expr()->eq('v.visit_location_id', 'vl.id'))
                 ->orderBy('v.id', 'DESC');

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Visit::class, 'v');
        $rsm->addJoinedEntityFromClassMetadata(VisitLocation::class, 'vl', 'v', 'visitLocation', [
            'id' => 'visit_location_id',
        ]);

        $query = $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm);

        return $query->getResult();
    }

    public function countVisitsByShortCode(string $shortCode, ?string $domain = null, ?DateRange $dateRange = null): int
    {
        /** @var QueryBuilder $qb */
        [$qb] = $this->createVisitsByShortCodeQueryBuilder($shortCode, $domain, $dateRange);
        $qb->select('COUNT(v.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createVisitsByShortCodeQueryBuilder(
        string $shortCode,
        ?string $domain,
        ?DateRange $dateRange
    ): array {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOne($shortCode, $domain) ?? -1;

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Visit::class, 'v')
           ->where($qb->expr()->eq('v.shortUrl', ':shortUrl'))
           ->setParameter('shortUrl', $shortUrl);

        // Apply date range filtering
        if ($dateRange !== null && $dateRange->getStartDate() !== null) {
            $qb->andWhere($qb->expr()->gte('v.date', ':startDate'))
               ->setParameter('startDate', $dateRange->getStartDate());
        }
        if ($dateRange !== null && $dateRange->getEndDate() !== null) {
            $qb->andWhere($qb->expr()->lte('v.date', ':endDate'))
               ->setParameter('endDate', $dateRange->getEndDate());
        }

        return [$qb, $shortUrl];
    }
}
