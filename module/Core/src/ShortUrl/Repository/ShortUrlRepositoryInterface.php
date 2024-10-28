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

/** @extends ObjectRepository<ShortUrl> */
interface ShortUrlRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    public function findOneWithDomainFallback(
        ShortUrlIdentifier $identifier,
        ShortUrlMode $shortUrlMode,
    ): ShortUrl|null;

    public function findOne(ShortUrlIdentifier $identifier, Specification|null $spec = null): ShortUrl|null;

    public function shortCodeIsInUse(ShortUrlIdentifier $identifier, Specification|null $spec = null): bool;

    public function shortCodeIsInUseWithLock(ShortUrlIdentifier $identifier, Specification|null $spec = null): bool;

    public function findOneMatching(ShortUrlCreation $creation): ShortUrl|null;

    public function findOneByImportedUrl(ImportedShlinkUrl $url): ShortUrl|null;
}
