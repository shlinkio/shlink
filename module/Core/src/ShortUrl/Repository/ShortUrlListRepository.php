<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

use function array_column;
use function sprintf;

class ShortUrlListRepository extends EntitySpecificationRepository implements ShortUrlListRepositoryInterface
{
    /**
     * @return ShortUrl[]
     */
    public function findList(ShortUrlsListFiltering $filtering): array
    {
        $qb = $this->createListQueryBuilder($filtering);
        $qb->select('DISTINCT s')
           ->setMaxResults($filtering->limit)
           ->setFirstResult($filtering->offset);

        $this->processOrderByForList($qb, $filtering);

        $result = $qb->getQuery()->getResult();
        if (OrderableField::isVisitsField($filtering->orderBy->field ?? '')) {
            return array_column($result, 0);
        }

        return $result;
    }

    private function processOrderByForList(QueryBuilder $qb, ShortUrlsListFiltering $filtering): void
    {
        // With no explicit order by, fallback to dateCreated-DESC
        $fieldName = $filtering->orderBy->field;
        if ($fieldName === null) {
            $qb->orderBy('s.dateCreated', 'DESC');
            return;
        }

        $order = $filtering->orderBy->direction;

        if (OrderableField::isBasicField($fieldName)) {
            $qb->orderBy('s.' . $fieldName, $order);
        } elseif (OrderableField::isVisitsField($fieldName)) {
            // FIXME This query is inefficient.
            //       Diagnostic: It might need to use a sub-query, as done with the tags list query.
            $qb->addSelect('COUNT(DISTINCT v)')
               ->leftJoin('s.visits', 'v', Join::WITH, $qb->expr()->andX(
                   $qb->expr()->eq('v.shortUrl', 's'),
                   $fieldName === OrderableField::NON_BOT_VISITS->value
                       ? $qb->expr()->eq('v.potentialBot', 'false')
                       : null,
               ))
               ->groupBy('s')
               ->orderBy('COUNT(DISTINCT v)', $order);
        }
    }

    public function countList(ShortUrlsCountFiltering $filtering): int
    {
        $qb = $this->createListQueryBuilder($filtering);
        $qb->select('COUNT(DISTINCT s)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createListQueryBuilder(ShortUrlsCountFiltering $filtering): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's')
            ->where('1=1');

        $dateRange = $filtering->dateRange;
        if ($dateRange?->startDate !== null) {
            $qb->andWhere($qb->expr()->gte('s.dateCreated', ':startDate'));
            $qb->setParameter('startDate', $dateRange->startDate, ChronosDateTimeType::CHRONOS_DATETIME);
        }
        if ($dateRange?->endDate !== null) {
            $qb->andWhere($qb->expr()->lte('s.dateCreated', ':endDate'));
            $qb->setParameter('endDate', $dateRange->endDate, ChronosDateTimeType::CHRONOS_DATETIME);
        }

        $searchTerm = $filtering->searchTerm;
        $tags = $filtering->tags;
        // Apply search term to every searchable field if not empty
        if (! empty($searchTerm)) {
            // Left join with tags only if no tags were provided. In case of tags, an inner join will be done later
            if (empty($tags)) {
                $qb->leftJoin('s.tags', 't');
            }

            // Apply general search conditions
            $conditions = [
                $qb->expr()->like('s.longUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
                $qb->expr()->like('s.title', ':searchPattern'),
                $qb->expr()->like('d.authority', ':searchPattern'),
            ];

            // Include default domain in search if provided
            if ($filtering->searchIncludesDefaultDomain) {
                $conditions[] = $qb->expr()->isNull('s.domain');
            }

            // Apply tag conditions, only when not filtering by all provided tags
            $tagsMode = $filtering->tagsMode ?? TagsMode::ANY;
            if (empty($tags) || $tagsMode === TagsMode::ANY) {
                $conditions[] = $qb->expr()->like('t.name', ':searchPattern');
            }

            $qb->leftJoin('s.domain', 'd')
               ->andWhere($qb->expr()->orX(...$conditions))
               ->setParameter('searchPattern', '%' . $searchTerm . '%');
        }

        // Filter by tags if provided
        if (! empty($tags)) {
            $tagsMode = $filtering->tagsMode ?? TagsMode::ANY;
            $tagsMode === TagsMode::ANY
                ? $qb->join('s.tags', 't')->andWhere($qb->expr()->in('t.name', $tags))
                : $this->joinAllTags($qb, $tags);
        }

        if ($filtering->excludeMaxVisitsReached) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('s.maxVisits'),
                $qb->expr()->gt(
                    's.maxVisits',
                    sprintf('(SELECT COUNT(innerV.id) FROM %s as innerV WHERE innerV.shortUrl=s)', Visit::class),
                ),
            ));
        }

        if ($filtering->excludePastValidUntil) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->isNull('s.validUntil'),
                    $qb->expr()->gte('s.validUntil', ':minValidUntil'),
                ))
                ->setParameter('minValidUntil', Chronos::now()->toDateTimeString());
        }

        $this->applySpecification($qb, $filtering->apiKey?->spec(), 's');

        return $qb;
    }

    private function joinAllTags(QueryBuilder $qb, array $tags): void
    {
        foreach ($tags as $index => $tag) {
            $alias = 't_' . $index;
            $qb->join('s.tags', $alias, Join::WITH, $alias . '.name = :tag' . $index)
               ->setParameter('tag' . $index, $tag);
        }
    }
}
