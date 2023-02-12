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
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithInlinedApiKeySpecsEnsuringJoin;
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

        $subQb = $this->createQueryBuilder('t');
        $subQb->select('t.id', 't.name');

        if (! $orderMainQuery) {
            $subQb->orderBy('t.name', $orderDir ?? 'ASC')
                  ->setMaxResults($filtering?->limit ?? PHP_INT_MAX)
                  ->setFirstResult($filtering?->offset ?? 0);
            // TODO Check if applying limit/offset ot visits sub-queries is needed with large amounts of tags
        }

        $conn = $this->getEntityManager()->getConnection();
        $buildVisitsSubQuery = static function (bool $excludeBots, string $aggregateAlias) use ($conn) {
            $visitsSubQuery = $conn->createQueryBuilder();
            $commonJoinCondition = $visitsSubQuery->expr()->eq('v.short_url_id', 's.id');
            $visitsJoin = ! $excludeBots
                ? $commonJoinCondition
                : $visitsSubQuery->expr()->and(
                    $commonJoinCondition,
                    $visitsSubQuery->expr()->eq('v.potential_bot', $conn->quote('0'))
                );

            return $visitsSubQuery
                ->select('st.tag_id AS tag_id', 'COUNT(DISTINCT v.id) AS ' . $aggregateAlias)
                ->from('visits', 'v')
                ->join('v', 'short_urls', 's', $visitsJoin)
                ->join('s', 'short_urls_in_tags', 'st', $visitsSubQuery->expr()->eq('st.short_url_id', 's.id'))
                ->groupBy('st.tag_id');
        };
        $allVisitsSubQuery = $buildVisitsSubQuery(false, 'visits');
        $nonBotVisitsSubQuery = $buildVisitsSubQuery(true, 'non_bot_visits');

        $searchTerm = $filtering?->searchTerm;
        if ($searchTerm !== null) {
            $subQb->andWhere($subQb->expr()->like('t.name', $conn->quote('%' . $searchTerm . '%')));
            // TODO Check if applying this to all sub-queries makes it faster or slower
        }

        $apiKey = $filtering?->apiKey;
        $applyApiKeyToNativeQuery = static fn (?ApiKey $apiKey, NativeQueryBuilder $nativeQueryBuilder) =>
            $apiKey?->mapRoles(static fn (Role $role, array $meta) => match ($role) {
                Role::DOMAIN_SPECIFIC => $nativeQueryBuilder->andWhere(
                    $nativeQueryBuilder->expr()->eq('s.domain_id', $conn->quote(Role::domainIdFromMeta($meta))),
                ),
                Role::AUTHORED_SHORT_URLS => $nativeQueryBuilder->andWhere(
                    $nativeQueryBuilder->expr()->eq('s.author_api_key_id', $conn->quote($apiKey->getId())),
                ),
            });

        // Apply API key specification to all sub-queries
        $this->applySpecification($subQb, new WithInlinedApiKeySpecsEnsuringJoin($apiKey), 't');
        $applyApiKeyToNativeQuery($apiKey, $allVisitsSubQuery);
        $applyApiKeyToNativeQuery($apiKey, $nonBotVisitsSubQuery);

        // A native query builder needs to be used here, because DQL and ORM query builders do not support
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, the whole list is loaded even with pagination, making it very inefficient.
        $nativeQb = $conn->createQueryBuilder();
        $nativeQb
            ->select(
                't.id_0 AS id',
                't.name_1 AS name',
                'v.visits',
                'v2.non_bot_visits',
                'COUNT(DISTINCT s.id) AS short_urls_count',
            )
            ->from('(' . $subQb->getQuery()->getSQL() . ')', 't') // @phpstan-ignore-line
            ->leftJoin('t', 'short_urls_in_tags', 'st', $nativeQb->expr()->eq('t.id_0', 'st.tag_id'))
            ->leftJoin('st', 'short_urls', 's', $nativeQb->expr()->eq('s.id', 'st.short_url_id'))
            ->leftJoin('t', '(' . $allVisitsSubQuery->getSQL() . ')', 'v', $nativeQb->expr()->eq('t.id_0', 'v.tag_id'))
            ->leftJoin('t', '(' . $nonBotVisitsSubQuery->getSQL() . ')', 'v2', $nativeQb->expr()->eq(
                't.id_0',
                'v2.tag_id',
            ))
            ->groupBy('t.id_0', 't.name_1', 'v.visits', 'v2.non_bot_visits');

        // Apply API key role conditions to the native query too, as they will affect the amounts on the aggregates
        $applyApiKeyToNativeQuery($apiKey, $nativeQb);

        if ($orderMainQuery) {
            $nativeQb
                ->orderBy(OrderableField::toSnakeCaseValidField($orderField), $orderDir ?? 'ASC')
                ->setMaxResults($filtering?->limit ?? PHP_INT_MAX)
                ->setFirstResult($filtering?->offset ?? 0);
        }

        // Add ordering by tag name, as a fallback in case of same amount, or as default ordering
        $nativeQb->addOrderBy('t.name_1', $orderMainQuery || $orderDir === null ? 'ASC' : $orderDir);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('name', 'tag');
        $rsm->addScalarResult('visits', 'visits');
        $rsm->addScalarResult('non_bot_visits', 'nonBotVisits');
        $rsm->addScalarResult('short_urls_count', 'shortUrlsCount');

        return map(
            $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm)->getResult(),
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
