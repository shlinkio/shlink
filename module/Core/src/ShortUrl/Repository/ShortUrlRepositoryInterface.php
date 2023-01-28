<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

interface ShortUrlRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    public function findOneWithDomainFallback(ShortUrlIdentifier $identifier, ShortUrlMode $shortUrlMode): ?ShortUrl;

    public function findOne(ShortUrlIdentifier $identifier, ?Specification $spec = null): ?ShortUrl;

    public function shortCodeIsInUse(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool;

    public function shortCodeIsInUseWithLock(ShortUrlIdentifier $identifier, ?Specification $spec = null): bool;

    public function findOneMatching(ShortUrlCreation $creation): ?ShortUrl;

    public function findOneByImportedUrl(ImportedShlinkUrl $url): ?ShortUrl;
}
