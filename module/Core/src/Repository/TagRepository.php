<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Happyr\DoctrineSpecification\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;

use function Functional\map;

class TagRepository extends EntitySpecificationRepository implements TagRepositoryInterface
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
    public function findTagsWithInfo(?Specification $spec = null): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t AS tag', 'COUNT(DISTINCT s.id) AS shortUrlsCount', 'COUNT(DISTINCT v.id) AS visitsCount')
           ->leftJoin('t.shortUrls', 's')
           ->leftJoin('s.visits', 'v')
           ->groupBy('t')
           ->orderBy('t.name', 'ASC');

        $this->applySpecification($qb, $spec, 't');

        $query = $qb->getQuery();

        return map(
            $query->getResult(),
            fn (array $row) => new TagInfo($row['tag'], (int) $row['shortUrlsCount'], (int) $row['visitsCount']),
        );
    }
}
