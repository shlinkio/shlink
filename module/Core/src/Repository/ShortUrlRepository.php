<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $orderBy = null
    ): array {
        $qb = $this->createListQueryBuilder($searchTerm, $tags);
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
        // Map public field names to column names
        $fieldNameMap = [
            'originalUrl' => 'longUrl',
            'longUrl' => 'longUrl',
            'shortCode' => 'shortCode',
            'dateCreated' => 'dateCreated',
        ];
        $fieldName = is_array($orderBy) ? key($orderBy) : $orderBy;
        $order = is_array($orderBy) ? $orderBy[$fieldName] : 'ASC';

        if (contains(['visits', 'visitsCount', 'visitCount'], $fieldName)) {
            $qb->addSelect('COUNT(DISTINCT v) AS totalVisits')
               ->leftJoin('s.visits', 'v')
               ->groupBy('s')
               ->orderBy('totalVisits', $order);

            return array_column($qb->getQuery()->getResult(), 0);
        }

        if (array_key_exists($fieldName, $fieldNameMap)) {
            $qb->orderBy('s.' . $fieldNameMap[$fieldName], $order);
        }
        return $qb->getQuery()->getResult();
    }

    public function countList(?string $searchTerm = null, array $tags = []): int
    {
        $qb = $this->createListQueryBuilder($searchTerm, $tags);
        $qb->select('COUNT(DISTINCT s)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createListQueryBuilder(?string $searchTerm = null, array $tags = []): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's');
        $qb->where('1=1');

        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            // Left join with tags only if no tags were provided. In case of tags, an inner join will be done later
            if (empty($tags)) {
                $qb->leftJoin('s.tags', 't');
            }

            $conditions = [
                $qb->expr()->like('s.longUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
                $qb->expr()->like('t.name', ':searchPattern'),
            ];

            // Unpack and apply search conditions
            $qb->andWhere($qb->expr()->orX(...$conditions));
            $qb->setParameter('searchPattern', '%' . $searchTerm . '%');
        }

        // Filter by tags if provided
        if (! empty($tags)) {
            $qb->join('s.tags', 't')
               ->andWhere($qb->expr()->in('t.name', $tags));
        }

        return $qb;
    }

    public function findOneByShortCode(string $shortCode): ?ShortUrl
    {
        $dql= <<<DQL
            SELECT s
              FROM Shlinkio\Shlink\Core\Entity\ShortUrl AS s
             WHERE s.shortCode = :shortCode
               AND (s.validSince <= :now OR s.validSince IS NULL)
               AND (s.validUntil >= :now OR s.validUntil IS NULL)
DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setMaxResults(1)
              ->setParameters([
                  'shortCode' => $shortCode,
                  'now' => Chronos::now(),
              ]);

        /** @var ShortUrl|null $result */
        $result = $query->getOneOrNullResult();
        return $result === null || $result->maxVisitsReached() ? null : $result;
    }
}
