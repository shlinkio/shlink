<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

use function array_column;
use function array_key_exists;
use function Functional\contains;
use function is_array;
use function key;

class ShortUrlRepository extends EntityRepository implements ShortUrlRepositoryInterface
{
    /**
     * @param string[] $tags
     * @param string|array|null $orderBy
     * @return ShortUrl[]
     */
    public function findList(
        ?int $limit = null,
        ?int $offset = null,
        ?string $searchTerm = null,
        array $tags = [],
        $orderBy = null,
        ?DateRange $dateRange = null
    ): array {
        $qb = $this->createListQueryBuilder($searchTerm, $tags, $dateRange);
        $qb->select('DISTINCT s');

        // Set limit and offset
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        // In case the ordering has been specified, the query could be more complex. Process it
        if ($orderBy !== null) {
            return $this->processOrderByForList($qb, $orderBy);
        }

        // With no order by, order by date and just return the list of ShortUrls
        $qb->orderBy('s.dateCreated');
        return $qb->getQuery()->getResult();
    }

    private function processOrderByForList(QueryBuilder $qb, $orderBy): array
    {
        $isArray = is_array($orderBy);
        $fieldName = $isArray ? key($orderBy) : $orderBy;
        $order = $isArray ? $orderBy[$fieldName] : 'ASC';

        if (contains(['visits', 'visitsCount', 'visitCount'], $fieldName)) {
            $qb->addSelect('COUNT(DISTINCT v) AS totalVisits')
               ->leftJoin('s.visits', 'v')
               ->groupBy('s')
               ->orderBy('totalVisits', $order);

            return array_column($qb->getQuery()->getResult(), 0);
        }

        // Map public field names to column names
        $fieldNameMap = [
            'originalUrl' => 'longUrl',
            'longUrl' => 'longUrl',
            'shortCode' => 'shortCode',
            'dateCreated' => 'dateCreated',
        ];
        if (array_key_exists($fieldName, $fieldNameMap)) {
            $qb->orderBy('s.' . $fieldNameMap[$fieldName], $order);
        }
        return $qb->getQuery()->getResult();
    }

    public function countList(?string $searchTerm = null, array $tags = [], ?DateRange $dateRange = null): int
    {
        $qb = $this->createListQueryBuilder($searchTerm, $tags, $dateRange);
        $qb->select('COUNT(DISTINCT s)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createListQueryBuilder(
        ?string $searchTerm = null,
        array $tags = [],
        ?DateRange $dateRange = null
    ): QueryBuilder {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's');
        $qb->where('1=1');

        if ($dateRange !== null && $dateRange->getStartDate() !== null) {
            $qb->andWhere($qb->expr()->gte('s.dateCreated', ':startDate'));
            $qb->setParameter('startDate', $dateRange->getStartDate());
        }
        if ($dateRange !== null && $dateRange->getEndDate() !== null) {
            $qb->andWhere($qb->expr()->lte('s.dateCreated', ':endDate'));
            $qb->setParameter('endDate', $dateRange->getEndDate());
        }

        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            // Left join with tags only if no tags were provided. In case of tags, an inner join will be done later
            if (empty($tags)) {
                $qb->leftJoin('s.tags', 't');
            }

            // Apply search conditions
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('s.longUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
                $qb->expr()->like('t.name', ':searchPattern')
            ));
            $qb->setParameter('searchPattern', '%' . $searchTerm . '%');
        }

        // Filter by tags if provided
        if (! empty($tags)) {
            $qb->join('s.tags', 't')
               ->andWhere($qb->expr()->in('t.name', $tags));
        }

        return $qb;
    }

    public function findOneByShortCode(string $shortCode, ?string $domain = null): ?ShortUrl
    {
        // When ordering DESC, Postgres puts nulls at the beginning while the rest of supported DB engines put them at
        // the bottom
        $dbPlatform = $this->getEntityManager()->getConnection()->getDatabasePlatform()->getName();
        $ordering = $dbPlatform === 'postgresql' ? 'ASC' : 'DESC';

        $dql = <<<DQL
            SELECT s
              FROM Shlinkio\Shlink\Core\Entity\ShortUrl AS s
         LEFT JOIN s.domain AS d
             WHERE s.shortCode = :shortCode
               AND (s.validSince <= :now OR s.validSince IS NULL)
               AND (s.validUntil >= :now OR s.validUntil IS NULL)
               AND (s.domain IS NULL OR d.authority = :domain)
          ORDER BY s.domain {$ordering}
DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setMaxResults(1)
              ->setParameters([
                  'shortCode' => $shortCode,
                  'now' => Chronos::now(),
                  'domain' => $domain,
              ]);

        // Since we ordered by domain, we will have first the URL matching provided domain, followed by the one
        // with no domain (if any), so it is safe to fetch 1 max result and we will get:
        //  * The short URL matching both the short code and the domain, or
        //  * The short URL matching the short code but without any domain, or
        //  * No short URL at all

        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $query->getOneOrNullResult();
        return $shortUrl !== null && ! $shortUrl->maxVisitsReached() ? $shortUrl : null;
    }

    public function shortCodeIsInUse(string $slug, ?string $domain = null): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(DISTINCT s.id)')
           ->from(ShortUrl::class, 's')
           ->where($qb->expr()->isNotNull('s.shortCode'))
           ->andWhere($qb->expr()->eq('s.shortCode', ':slug'))
           ->setParameter('slug', $slug);

        if ($domain !== null) {
            $qb->join('s.domain', 'd')
               ->andWhere($qb->expr()->eq('d.authority', ':authority'))
               ->setParameter('authority', $domain);
        } else {
            $qb->andWhere($qb->expr()->isNull('s.domain'));
        }

        $result = (int) $qb->getQuery()->getSingleScalarResult();
        return $result > 0;
    }
}
