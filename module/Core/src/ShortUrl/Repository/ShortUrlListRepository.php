<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;

use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function sprintf;

/** @extends EntitySpecificationRepository<ShortUrl> */
class ShortUrlListRepository extends EntitySpecificationRepository implements ShortUrlListRepositoryInterface
{
    /**
     * @return ShortUrlWithDeps[]
     */
    public function findList(ShortUrlsListFiltering $filtering): array
    {
        $buildVisitsSubQuery = function (string $alias, bool $excludingBots): string {
            $vqb = $this->getEntityManager()->createQueryBuilder();
            $vqb->select('COALESCE(SUM(' . $alias . '.count), 0)')
                ->from(ShortUrlVisitsCount::class, $alias)
                ->where($vqb->expr()->eq($alias . '.shortUrl', 's'));

            if ($excludingBots) {
                $vqb->andWhere($vqb->expr()->eq($alias . '.potentialBot', ':potentialBot'));
            }

            return $vqb->getDQL();
        };

        $qb = $this->createListQueryBuilder($filtering);
        $qb->select(
            'DISTINCT s AS shortUrl, d.authority',
            '(' . $buildVisitsSubQuery('v', excludingBots: false) . ') AS ' . OrderableField::VISITS->value,
            '(' . $buildVisitsSubQuery('v2', excludingBots: true) . ') AS ' . OrderableField::NON_BOT_VISITS->value,
            // This is added only to have a consistent order by title between database engines
            'COALESCE(s.title, \'\') AS title',
        )
           ->setMaxResults($filtering->limit)
           ->setFirstResult($filtering->offset)
           // This param is used in one of the sub-queries, but needs to set in the parent query
           ->setParameter('potentialBot', false);

        $this->processOrderByForList($qb, $filtering);

        /** @var array{shortUrl: ShortUrl, visits: string, nonBotVisits: string, authority: string|null}[] $result */
        $result = $qb->getQuery()->getResult();
        return map($result, static fn (array $s) => ShortUrlWithDeps::fromArray($s));
    }

    private function processOrderByForList(QueryBuilder $qb, ShortUrlsListFiltering $filtering): void
    {
        $fieldName = $filtering->orderBy->field;
        $direction = $filtering->orderBy->direction;
        [$sort, $order] = match (true) {
            // With no explicit order by, fallback to dateCreated-DESC
            $fieldName === null => ['s.dateCreated', 'DESC'],
            $fieldName === OrderableField::VISITS->value,
            $fieldName === OrderableField::NON_BOT_VISITS->value,
            $fieldName === OrderableField::TITLE->value => [$fieldName, $direction],
            default => ['s.' . $fieldName, $direction],
        };

        $qb->orderBy($sort, $order);
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
           ->leftJoin('s.domain', 'd')
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
        if (! empty($searchTerm)) {
            // Left join with tags only if no tags were provided. In case of tags, an inner join will be done later
            if (empty($tags)) {
                $qb->leftJoin('s.tags', 't');
            }

            // Apply search term to every "searchable" field
            $conditions = [
                $qb->expr()->like('s.longUrl', ':searchPattern'),
                $qb->expr()->like('s.shortCode', ':searchPattern'),
                $qb->expr()->like('s.title', ':searchPattern'),
                $qb->expr()->like('d.authority', ':searchPattern'),
            ];

            // Include default domain in search if included, and a domain was not explicitly provided
            if ($filtering->searchIncludesDefaultDomain && $filtering->domain === null) {
                $conditions[] = $qb->expr()->isNull('s.domain');
            }

            // Apply tag conditions, only when not filtering by all provided tags
            $tagsMode = $filtering->tagsMode;
            if (empty($tags) || $tagsMode === TagsMode::ANY) {
                $conditions[] = $qb->expr()->like('t.name', ':searchPattern');
            }

            $qb->andWhere($qb->expr()->orX(...$conditions))
               ->setParameter('searchPattern', '%' . $searchTerm . '%');
        }

        // Filter by tags if provided
        if (! empty($tags)) {
            $tagsMode = $filtering->tagsMode;
            $tagsMode === TagsMode::ANY
                ? $qb->join('s.tags', 't')->andWhere($qb->expr()->in('t.name', $tags))
                : $this->joinAllTags($qb, $tags);
        }

        if ($filtering->domain !== null) {
            if ($filtering->domain === Domain::DEFAULT_AUTHORITY) {
                $qb->andWhere($qb->expr()->isNull('s.domain'));
            } else {
                $qb->andWhere($qb->expr()->eq('d.authority', ':domain'))
                   ->setParameter('domain', $filtering->domain);
            }
        }

        if ($filtering->excludeMaxVisitsReached) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('s.maxVisits'),
                $qb->expr()->gt(
                    's.maxVisits',
                    sprintf(
                        '(SELECT COALESCE(SUM(vc.count), 0) FROM %s as vc WHERE vc.shortUrl=s)',
                        ShortUrlVisitsCount::class,
                    ),
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
