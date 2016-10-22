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
        $qb = $this->createListQueryBuilder($searchTerm);
        $qb->select('s');

        if (isset($limit)) {
            $qb->setMaxResults($limit);
        }
        if (isset($offset)) {
            $qb->setFirstResult($offset);
        }
        if (isset($orderBy)) {
            if (is_string($orderBy)) {
                $qb->orderBy($orderBy);
            } elseif (is_array($orderBy)) {
                $key = key($orderBy);
                $qb->orderBy($key, $orderBy[$key]);
            }
        } else {
            $qb->orderBy('s.dateCreated');
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
        $qb = $this->createListQueryBuilder($searchTerm);
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

        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            $conditions = [
                $qb->expr()->like('s.originalUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
            ];

            // Unpack and apply search conditions
            $qb->where($qb->expr()->orX(...$conditions));
            $searchTerm = '%' . $searchTerm . '%';
            $qb->setParameter('searchPattern', $searchTerm);
        }

        return $qb;
    }
}
