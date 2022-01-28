<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

interface ShortUrlRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    public function findList(ShortUrlsListFiltering $filtering): array;

    public function countList(ShortUrlsCountFiltering $filtering): int;

    public function findOneWithDomainFallback(ShortUrlIdentifier $identifier): ?ShortUrl;

    public function findOne(ShortUrlIdentifier $identifier, ?Specification $spec = null): ?ShortUrl;

    public function shortCodeIsInUse(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool;

    public function shortCodeIsInUseWithLock(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool;

    public function findOneMatching(ShortUrlMeta $meta): ?ShortUrl;

    public function findOneByImportedUrl(ImportedShlinkUrl $url): ?ShortUrl;

    public function findCrawlableShortCodes(): iterable;
}
