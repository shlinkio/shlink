<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Shlinkio\Shlink\Core\Entity\Tag;

class TagRepository extends EntityRepository implements TagRepositoryInterface
{
    /**
     * Delete the tags identified by provided names
     *
     * @param array $names
     * @return int The number of affected entries
     */
    public function deleteByName(array $names)
    {
        if (empty($names)) {
            return 0;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Tag::class, 't')
           ->where($qb->expr()->in('t.name', $names));

        return $qb->getQuery()->execute();
    }
}
