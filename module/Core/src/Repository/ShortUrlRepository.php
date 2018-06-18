<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

class ShortUrlRepository extends EntityRepository implements ShortUrlRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $searchTerm
     * @param array $tags
     * @param string|array|null $orderBy
     * @return \Shlinkio\Shlink\Core\Entity\ShortUrl[]
     */
    public function findList(
        int $limit = null,
        int $offset = null,
        string $searchTerm = null,
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

    protected function processOrderByForList(QueryBuilder $qb, $orderBy)
    {
        $fieldName = \is_array($orderBy) ? \key($orderBy) : $orderBy;
        $order = \is_array($orderBy) ? $orderBy[$fieldName] : 'ASC';

        if (\in_array($fieldName, ['visits', 'visitsCount', 'visitCount'], true)) {
            $qb->addSelect('COUNT(v) AS totalVisits')
               ->leftJoin('s.visits', 'v')
               ->groupBy('s')
               ->orderBy('totalVisits', $order);

            return \array_column($qb->getQuery()->getResult(), 0);
        } elseif (\in_array($fieldName, ['originalUrl', 'shortCode', 'dateCreated'], true)) {
            $qb->orderBy('s.' . $fieldName, $order);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Counts the number of elements in a list using provided filtering data
     *
     * @param null|string $searchTerm
     * @param array $tags
     * @return int
     */
    public function countList(string $searchTerm = null, array $tags = []): int
    {
        $qb = $this->createListQueryBuilder($searchTerm, $tags);
        $qb->select('COUNT(DISTINCT s)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null|string $searchTerm
     * @param array $tags
     * @return QueryBuilder
     */
    protected function createListQueryBuilder(string $searchTerm = null, array $tags = []): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's');
        $qb->where('1=1');

        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            $qb->leftJoin('s.tags', 't');

            $conditions = [
                $qb->expr()->like('s.originalUrl', ':searchPattern'),
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

    /**
     * @param string $shortCode
     * @return ShortUrl|null
     */
    public function findOneByShortCode(string $shortCode): ?ShortUrl
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->eq('s.shortCode', ':shortCode'))
           ->setParameter('shortCode', $shortCode)
           ->andWhere($qb->expr()->orX(
               $qb->expr()->lte('s.validSince', ':now'),
               $qb->expr()->isNull('s.validSince')
           ))
           ->andWhere($qb->expr()->orX(
               $qb->expr()->gte('s.validUntil', ':now'),
               $qb->expr()->isNull('s.validUntil')
           ))
           ->setParameter('now', $now)
           ->setMaxResults(1);

        /** @var ShortUrl|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();
        return $result === null || $result->maxVisitsReached() ? null : $result;
    }
}
