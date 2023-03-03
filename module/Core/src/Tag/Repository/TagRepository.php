<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Repository;

use Doctrine\DBAL\Query\QueryBuilder as NativeQueryBuilder;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\OrderableField;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Core\Tag\Spec\CountTagsWithName;
use Shlinkio\Shlink\Rest\ApiKey\Role;
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
        $orderField = $filtering?->orderBy?->field;
        $orderDir = $filtering?->orderBy?->direction;
        $orderMainQuery = $orderField !== null && OrderableField::isAggregateField($orderField);

        $conn = $this->getEntityManager()->getConnection();
        $tagsSubQb = $conn->createQueryBuilder();
        $tagsSubQb
            ->select('t.id', 't.name', 'COUNT(DISTINCT s.id) AS short_urls_count')
            ->from('tags', 't')
            ->leftJoin('t', 'short_urls_in_tags', 'st', $tagsSubQb->expr()->eq('st.tag_id', 't.id'))
            ->leftJoin('st', 'short_urls', 's', $tagsSubQb->expr()->eq('st.short_url_id', 's.id'))
            ->groupBy('t.id', 't.name');

        if (! $orderMainQuery) {
            $tagsSubQb
                ->orderBy('t.name', $orderDir ?? 'ASC')
                ->setMaxResults($filtering?->limit ?? PHP_INT_MAX)
                ->setFirstResult($filtering?->offset ?? 0);
            // TODO Check if applying limit/offset ot visits sub-queries is needed with large amounts of tags
        }

        $buildVisitsSubQb = static function (bool $excludeBots, string $aggregateAlias) use ($conn) {
            $visitsSubQb = $conn->createQueryBuilder();
            $commonJoinCondition = $visitsSubQb->expr()->eq('v.short_url_id', 's.id');
            $visitsJoin = ! $excludeBots
                ? $commonJoinCondition
                : $visitsSubQb->expr()->and(
                    $commonJoinCondition,
                    $visitsSubQb->expr()->eq('v.potential_bot', $conn->quote('0')),
                );

            return $visitsSubQb
                ->select('st.tag_id AS tag_id', 'COUNT(DISTINCT v.id) AS ' . $aggregateAlias)
                ->from('visits', 'v')
                ->join('v', 'short_urls', 's', $visitsJoin) // @phpstan-ignore-line
                ->join('s', 'short_urls_in_tags', 'st', $visitsSubQb->expr()->eq('st.short_url_id', 's.id'))
                ->groupBy('st.tag_id');
        };
        $allVisitsSubQb = $buildVisitsSubQb(false, 'visits');
        $nonBotVisitsSubQb = $buildVisitsSubQb(true, 'non_bot_visits');

        $searchTerm = $filtering?->searchTerm;
        if ($searchTerm !== null) {
            $tagsSubQb->andWhere($tagsSubQb->expr()->like('t.name', $conn->quote('%' . $searchTerm . '%')));
            // TODO Check if applying this to all sub-queries makes it faster or slower
        }

        $apiKey = $filtering?->apiKey;
        $applyApiKeyToNativeQb = static fn (?ApiKey $apiKey, NativeQueryBuilder $qb) =>
            $apiKey?->mapRoles(static fn (Role $role, array $meta) => match ($role) {
                Role::DOMAIN_SPECIFIC => $qb->andWhere(
                    $qb->expr()->eq('s.domain_id', $conn->quote(Role::domainIdFromMeta($meta))),
                ),
                Role::AUTHORED_SHORT_URLS => $qb->andWhere(
                    $qb->expr()->eq('s.author_api_key_id', $conn->quote($apiKey->getId())),
                ),
            });

        // Apply API key specification to all sub-queries
        $applyApiKeyToNativeQb($apiKey, $tagsSubQb);
        $applyApiKeyToNativeQb($apiKey, $allVisitsSubQb);
        $applyApiKeyToNativeQb($apiKey, $nonBotVisitsSubQb);

        // A native query builder needs to be used here, because DQL and ORM query builders do not support
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, the whole list is loaded even with pagination, making it very inefficient.
        $mainQb = $conn->createQueryBuilder();
        $mainQb
            ->select(
                't.id AS id',
                't.name AS name',
                'COALESCE(v.visits, 0) AS visits', // COALESCE required for postgres to properly order
                'COALESCE(v2.non_bot_visits, 0) AS non_bot_visits',
                'COALESCE(t.short_urls_count, 0) AS short_urls_count',
            )
            ->from('(' . $tagsSubQb->getSQL() . ')', 't')
            ->leftJoin('t', '(' . $allVisitsSubQb->getSQL() . ')', 'v', $mainQb->expr()->eq('t.id', 'v.tag_id'))
            ->leftJoin('t', '(' . $nonBotVisitsSubQb->getSQL() . ')', 'v2', $mainQb->expr()->eq(
                't.id',
                'v2.tag_id',
            ));

        if ($orderMainQuery) {
            $mainQb
                ->orderBy(OrderableField::toSnakeCaseValidField($orderField), $orderDir ?? 'ASC')
                ->setMaxResults($filtering?->limit ?? PHP_INT_MAX)
                ->setFirstResult($filtering?->offset ?? 0);
        }

        // Add ordering by tag name, as a fallback in case of same amount, or as default ordering
        $mainQb->addOrderBy('t.name', $orderMainQuery || $orderDir === null ? 'ASC' : $orderDir);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('name', 'tag');
        $rsm->addScalarResult('visits', 'visits');
        $rsm->addScalarResult('non_bot_visits', 'nonBotVisits');
        $rsm->addScalarResult('short_urls_count', 'shortUrlsCount');

        return map(
            $this->getEntityManager()->createNativeQuery($mainQb->getSQL(), $rsm)->getResult(),
            TagInfo::fromRawData(...),
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
