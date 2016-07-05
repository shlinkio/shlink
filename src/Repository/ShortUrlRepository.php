<?php
namespace Acelaya\UrlShortener\Repository;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ShortUrlRepository extends EntityRepository implements ShortUrlRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $searchTerm
     * @param string|array|null $orderBy
     * @return ShortUrl[]
     */
    public function findList($limit = null, $offset = null, $searchTerm = null, $orderBy = null)
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($limit)) {
            $qb->setMaxResults($limit);
        }
        if (isset($offset)) {
            $qb->setFirstResult($offset);
        }
        if (isset($searchTerm)) {
            // TODO
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
     * @param null $searchTerm
     * @return int
     */
    public function countList($searchTerm = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(s)')
           ->from(ShortUrl::class, 's');

        if (isset($searchTerm)) {
            // TODO
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
