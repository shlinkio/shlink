<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Core\Entity\Domain;

interface DomainRepositoryInterface extends ObjectRepository
{
    /**
     * @return Domain[]
     */
    public function findDomainsWithout(?string $excludedAuthority = null): array;
}
