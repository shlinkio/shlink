<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;

use function Functional\map;

class TagRepository extends EntityRepository implements TagRepositoryInterface
{
    public function deleteByName(array $names): int
    {
        if (empty($names)) {
            return 0;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Tag::class, 't')
           ->where($qb->expr()->in('t.name', $names));

        return $qb->getQuery()->execute();
    }

    /**
     * @return TagInfo[]
     */
    public function findTagsWithInfo(): array
    {
        $dql = <<<DQL
            SELECT t AS tag, COUNT(DISTINCT s.id) AS shortUrlsCount, COUNT(DISTINCT v.id) AS visitsCount
            FROM Shlinkio\Shlink\Core\Entity\Tag t
            LEFT JOIN t.shortUrls s
            LEFT JOIN s.visits v
            GROUP BY t
            ORDER BY t.name ASC
        DQL;
        $query = $this->getEntityManager()->createQuery($dql);

        return map(
            $query->getResult(),
            fn (array $row) => new TagInfo($row['tag'], (int) $row['shortUrlsCount'], (int) $row['visitsCount']),
        );
    }
}
