<?php
namespace Acelaya\UrlShortener\Repository;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Doctrine\ORM\EntityRepository;

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
}
