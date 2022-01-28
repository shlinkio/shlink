<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Core\Tag\Spec\CountTagsWithName;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithInlinedApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Functional\contains;
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
        $orderField = $filtering?->orderBy()?->orderField();
        $orderDir = $filtering?->orderBy()?->orderDirection();
        $orderMainQuery = contains(['shortUrlsCount', 'visitsCount'], $orderField);

        $conn = $this->getEntityManager()->getConnection();
        $subQb = $this->createQueryBuilder('t');
        $subQb->select('t.id', 't.name');

        if (! $orderMainQuery) {
            $subQb->orderBy('t.name', $orderDir ?? 'ASC')
                  ->setMaxResults($filtering?->limit() ?? PHP_INT_MAX)
                  ->setFirstResult($filtering?->offset() ?? 0);
        }

        $searchTerm = $filtering?->searchTerm();
        if ($searchTerm !== null) {
            $subQb->andWhere($subQb->expr()->like('t.name', $conn->quote('%' . $searchTerm . '%')));
        }

        $apiKey = $filtering?->apiKey();
        $this->applySpecification($subQb, new WithInlinedApiKeySpecsEnsuringJoin($apiKey), 't');

        // A native query builder needs to be used here, because DQL and ORM query builders do not support
        // sub-queries at "from" and "join" level.
        // If no sub-query is used, the whole list is loaded even with pagination, making it very inefficient.
        $nativeQb = $conn->createQueryBuilder();
        $nativeQb
            ->select(
                't.id_0 AS id',
                't.name_1 AS name',
                'COUNT(DISTINCT s.id) AS short_urls_count',
                'COUNT(DISTINCT v.id) AS visits_count',
            )
            ->from('(' . $subQb->getQuery()->getSQL() . ')', 't')
            ->leftJoin('t', 'short_urls_in_tags', 'st', $nativeQb->expr()->eq('t.id_0', 'st.tag_id'))
            ->leftJoin('st', 'short_urls', 's', $nativeQb->expr()->eq('s.id', 'st.short_url_id'))
            ->leftJoin('st', 'visits', 'v', $nativeQb->expr()->eq('s.id', 'v.short_url_id'))
            ->groupBy('t.id_0', 't.name_1');

        // Apply API key role conditions to the native query too, as they will affect the amounts on the aggregates
        $apiKey?->mapRoles(static fn (string $roleName, array $meta) => match ($roleName) {
            Role::DOMAIN_SPECIFIC => $nativeQb->andWhere(
                $nativeQb->expr()->eq('s.domain_id', $conn->quote(Role::domainIdFromMeta($meta))),
            ),
            Role::AUTHORED_SHORT_URLS => $nativeQb->andWhere(
                $nativeQb->expr()->eq('s.author_api_key_id', $conn->quote($apiKey->getId())),
            ),
            default => $nativeQb,
        });

        if ($orderMainQuery) {
            $nativeQb
                ->orderBy(
                    $orderField === 'shortUrlsCount' ? 'short_urls_count' : 'visits_count',
                    $orderDir ?? 'ASC',
                )
                ->setMaxResults($filtering?->limit() ?? PHP_INT_MAX)
                ->setFirstResult($filtering?->offset() ?? 0);
        }

        // Add ordering by tag name, as a fallback in case of same amount, or as default ordering
        $nativeQb->addOrderBy('t.name_1', $orderMainQuery || $orderDir === null ? 'ASC' : $orderDir);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('name', 'tag');
        $rsm->addScalarResult('short_urls_count', 'shortUrlsCount');
        $rsm->addScalarResult('visits_count', 'visitsCount');

        return map(
            $this->getEntityManager()->createNativeQuery($nativeQb->getSQL(), $rsm)->getResult(),
            static fn (array $row) => new TagInfo($row['tag'], (int) $row['shortUrlsCount'], (int) $row['visitsCount']),
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
