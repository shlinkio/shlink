<?php
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
    public function findList($limit = null, $offset = null, $searchTerm = null, array $tags = [], $orderBy = null)
    {
        $qb = $this->createListQueryBuilder($searchTerm, $tags);
        $qb->select('s');

        // Set limit and offset
        if (isset($limit)) {
            $qb->setMaxResults($limit);
        }
        if (isset($offset)) {
            $qb->setFirstResult($offset);
        }

        // In case the ordering has been specified, the query could be more complex. Process it
        if (isset($orderBy)) {
            return $this->processOrderByForList($qb, $orderBy);
        }

        // With no order by, order by date and just return the list of ShortUrls
        $qb->orderBy('s.dateCreated');
        return $qb->getQuery()->getResult();
    }

    protected function processOrderByForList(QueryBuilder $qb, $orderBy)
    {
        $fieldName = is_array($orderBy) ? key($orderBy) : $orderBy;
        $order = is_array($orderBy) ? $orderBy[$fieldName] : 'ASC';

        if ($fieldName === 'visits') {
            $qb->addSelect('COUNT(v) AS totalVisits')
               ->leftJoin('s.visits', 'v')
               ->groupBy('s')
               ->orderBy('totalVisits', $order);

            return array_column($qb->getQuery()->getResult(), 0);
        } elseif (in_array($fieldName, [
            'originalUrl',
            'shortCode',
            'dateCreated',
        ])) {
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
    public function countList($searchTerm = null, array $tags = [])
    {
        $qb = $this->createListQueryBuilder($searchTerm, $tags);
        $qb->select('COUNT(s)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null|string $searchTerm
     * @param array $tags
     * @return QueryBuilder
     */
    protected function createListQueryBuilder($searchTerm = null, array $tags = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's');
        $qb->where('1=1');

        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            $conditions = [
                $qb->expr()->like('s.originalUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
            ];

            // Unpack and apply search conditions
            $qb->andWhere($qb->expr()->orX(...$conditions));
            $searchTerm = '%' . $searchTerm . '%';
            $qb->setParameter('searchPattern', $searchTerm);
        }

        // Filter by tags if provided
        if (! empty($tags)) {
            $qb->join('s.tags', 't')
               ->andWhere($qb->expr()->in('t.name', $tags));
        }

        return $qb;
    }
}
