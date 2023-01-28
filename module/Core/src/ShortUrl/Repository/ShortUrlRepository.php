<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

use function count;
use function strtolower;

class ShortUrlRepository extends EntitySpecificationRepository implements ShortUrlRepositoryInterface
{
    public function findOneWithDomainFallback(ShortUrlIdentifier $identifier, ShortUrlMode $shortUrlMode): ?ShortUrl
    {
        // When ordering DESC, Postgres puts nulls at the beginning while the rest of supported DB engines put them at
        // the bottom
        $dbPlatform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $ordering = $dbPlatform instanceof PostgreSQLPlatform ? 'ASC' : 'DESC';
        $isStrict = $shortUrlMode === ShortUrlMode::STRICT;

        $qb = $this->createQueryBuilder('s');
        $qb->leftJoin('s.domain', 'd')
           ->where($qb->expr()->eq($isStrict ? 's.shortCode' : 'LOWER(s.shortCode)', ':shortCode'))
           ->setParameter('shortCode', $isStrict ? $identifier->shortCode : strtolower($identifier->shortCode))
           ->andWhere($qb->expr()->orX(
               $qb->expr()->isNull('s.domain'),
               $qb->expr()->eq('d.authority', ':domain'),
           ))
           ->setParameter('domain', $identifier->domain);

        // Since we order by domain, we will have first the URL matching provided domain, followed by the one
        // with no domain (if any), so it is safe to fetch 1 max result, and we will get:
        //  * The short URL matching both the short code and the domain, or
        //  * The short URL matching the short code but without any domain, or
        //  * No short URL at all
        $qb->orderBy('s.domain', $ordering)
           ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findOne(ShortUrlIdentifier $identifier, ?Specification $spec = null): ?ShortUrl
    {
        $qb = $this->createFindOneQueryBuilder($identifier, $spec);
        $qb->select('s');

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function shortCodeIsInUse(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool
    {
        return $this->doShortCodeIsInUse($identifier, $spec, null);
    }

    public function shortCodeIsInUseWithLock(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool
    {
        return $this->doShortCodeIsInUse($identifier, $spec, LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @param LockMode::PESSIMISTIC_WRITE|null $lockMode
     */
    private function doShortCodeIsInUse(ShortUrlIdentifier $identifier, ?Specification $spec, ?int $lockMode): bool
    {
        $qb = $this->createFindOneQueryBuilder($identifier, $spec)->select('s.id');
        $query = $qb->getQuery();

        if ($lockMode !== null) {
            $query = $query->setLockMode($lockMode);
        }

        return $query->getOneOrNullResult() !== null;
    }

    private function createFindOneQueryBuilder(ShortUrlIdentifier $identifier, ?Specification $spec): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ShortUrl::class, 's')
           ->where($qb->expr()->isNotNull('s.shortCode'))
           ->andWhere($qb->expr()->eq('s.shortCode', ':slug'))
           ->setParameter('slug', $identifier->shortCode)
           ->setMaxResults(1);

        $this->whereDomainIs($qb, $identifier->domain);

        $this->applySpecification($qb, $spec, 's');

        return $qb;
    }

    public function findOneMatching(ShortUrlCreation $creation): ?ShortUrl
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s')
           ->from(ShortUrl::class, 's')
           ->where($qb->expr()->eq('s.longUrl', ':longUrl'))
           ->setParameter('longUrl', $creation->longUrl)
           ->setMaxResults(1)
           ->orderBy('s.id');

        if ($creation->hasCustomSlug()) {
            $qb->andWhere($qb->expr()->eq('s.shortCode', ':slug'))
               ->setParameter('slug', $creation->customSlug);
        }
        if ($creation->hasMaxVisits()) {
            $qb->andWhere($qb->expr()->eq('s.maxVisits', ':maxVisits'))
               ->setParameter('maxVisits', $creation->maxVisits);
        }
        if ($creation->hasValidSince()) {
            $qb->andWhere($qb->expr()->eq('s.validSince', ':validSince'))
               ->setParameter('validSince', $creation->validSince, ChronosDateTimeType::CHRONOS_DATETIME);
        }
        if ($creation->hasValidUntil()) {
            $qb->andWhere($qb->expr()->eq('s.validUntil', ':validUntil'))
               ->setParameter('validUntil', $creation->validUntil, ChronosDateTimeType::CHRONOS_DATETIME);
        }
        if ($creation->hasDomain()) {
            $qb->join('s.domain', 'd')
               ->andWhere($qb->expr()->eq('d.authority', ':domain'))
               ->setParameter('domain', $creation->domain);
        }

        $apiKey = $creation->apiKey;
        if ($apiKey !== null) {
            $this->applySpecification($qb, $apiKey->spec(), 's');
        }

        $tags = $creation->tags;
        $tagsAmount = count($tags);
        if ($tagsAmount === 0) {
            return $qb->getQuery()->getOneOrNullResult();
        }

        $this->joinAllTags($qb, $tags);

        // If tags where provided, we need an extra join to see the amount of tags that every short URL has, so that we
        // can discard those that also have more tags, making sure only those fully matching are included.
        $qb->join('s.tags', 't')
           ->groupBy('s')
           ->having($qb->expr()->eq('COUNT(t.id)', ':tagsAmount'))
           ->setParameter('tagsAmount', $tagsAmount);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function joinAllTags(QueryBuilder $qb, array $tags): void
    {
        foreach ($tags as $index => $tag) {
            $alias = 't_' . $index;
            $qb->join('s.tags', $alias, Join::WITH, $alias . '.name = :tag' . $index)
               ->setParameter('tag' . $index, $tag);
        }
    }

    public function findOneByImportedUrl(ImportedShlinkUrl $url): ?ShortUrl
    {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere($qb->expr()->eq('s.importOriginalShortCode', ':shortCode'))
           ->setParameter('shortCode', $url->shortCode)
           ->andWhere($qb->expr()->eq('s.importSource', ':importSource'))
           ->setParameter('importSource', $url->source->value)
           ->setMaxResults(1);

        $this->whereDomainIs($qb, $url->domain);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function whereDomainIs(QueryBuilder $qb, ?string $domain): void
    {
        if ($domain !== null) {
            $qb->join('s.domain', 'd')
               ->andWhere($qb->expr()->eq('d.authority', ':authority'))
               ->setParameter('authority', $domain);
        } else {
            $qb->andWhere($qb->expr()->isNull('s.domain'));
        }
    }
}
