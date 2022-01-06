<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Core\Tag\Spec\CountTagsWithName;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Functional\map;

use const PHP_INT_MAX;

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
    public function findTagsWithInfo(?TagsListFiltering $filtering = null): array
    {
        $subQb = $this->createQueryBuilder('t');
        $subQb->select('t.id', 't.name')
              ->orderBy('t.name', 'ASC') // TODO Make dynamic
              ->setMaxResults($filtering?->limit() ?? PHP_INT_MAX)
              ->setFirstResult($filtering?->offset() ?? 0);

        $searchTerm = $filtering?->searchTerm();
        if ($searchTerm !== null) {
            // FIXME This value cannot be added via params, so it needs to be sanitized
            $subQb->andWhere($subQb->expr()->like('t.name', '\'%' . $searchTerm . '%\''));
        }

        $subQuery = $subQb->getQuery()->getSQL();

        // A native query builder needs to be used here because DQL and ORM query builders do not accept
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, the whole list is loaded even with pagination, making it very inefficient.
        $nativeQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $nativeQb
            ->select(
                't.id_0 AS id',
                't.name_1 AS name',
                'COUNT(DISTINCT s.id) AS short_urls_count',
                'COUNT(DISTINCT v.id) AS visits_count',
            )
            ->from('(' . $subQuery . ')', 't')
            ->leftJoin('t', 'short_urls_in_tags', 'st', $nativeQb->expr()->eq('t.id_0', 'st.tag_id'))
            ->leftJoin('st', 'short_urls', 's', $nativeQb->expr()->eq('s.id', 'st.short_url_id'))
            ->leftJoin('st', 'visits', 'v', $nativeQb->expr()->eq('s.id', 'v.short_url_id'))
            ->groupBy('t.id_0', 't.name_1')
            ->orderBy('t.name_1', 'ASC'); // TODO Make dynamic

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Tag::class, 't');
        $rsm->addScalarResult('short_urls_count', 'shortUrlsCount');
        $rsm->addScalarResult('visits_count', 'visitsCount');

        // TODO Apply API key cond to main query
//        $apiKey = $filtering?->apiKey();
//        if ($apiKey !== null) {
//            $this->applySpecification($nativeQb, $apiKey->spec(false, 'shortUrls'), 't');
//        }

        return map(
            $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm)->getResult(),
            static fn (array $row) => new TagInfo($row[0], (int) $row['shortUrlsCount'], (int) $row['visitsCount']),
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
