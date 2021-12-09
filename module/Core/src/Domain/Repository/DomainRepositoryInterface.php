<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface DomainRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    /**
     * @return Domain[]
     */
    public function findDomains(?ApiKey $apiKey = null): array;

    public function findOneByAuthority(string $authority, ?ApiKey $apiKey = null): ?Domain;
}
