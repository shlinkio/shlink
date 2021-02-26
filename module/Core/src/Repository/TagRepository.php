<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Spec\CountTagsWithName;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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
    public function findTagsWithInfo(?ApiKey $apiKey = null): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t AS tag', 'COUNT(DISTINCT s.id) AS shortUrlsCount', 'COUNT(DISTINCT v.id) AS visitsCount')
           ->leftJoin('t.shortUrls', 's')
           ->leftJoin('s.visits', 'v')
           ->groupBy('t')
           ->orderBy('t.name', 'ASC');

        if ($apiKey !== null) {
            $this->applySpecification($qb, $apiKey->spec(false, 'shortUrls'), 't');
        }

        $query = $qb->getQuery();

        return map(
            $query->getResult(),
            fn (array $row) => new TagInfo($row['tag'], (int) $row['shortUrlsCount'], (int) $row['visitsCount']),
        );
    }

    public function tagExists(string $tag, ?ApiKey $apiKey = null): bool
    {
        $result = (int) $this->matchSingleScalarResult(Spec::andX(
            new CountTagsWithName($tag),
            new WithApiKeySpecsEnsuringJoin($apiKey),
        ));

        return $result > 0;
    }
}
