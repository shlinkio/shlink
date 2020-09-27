<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Shlinkio\Shlink\Core\Entity\Domain;

interface DomainServiceInterface
{
    /**
     * @return Domain[]
     */
    public function listDomainsWithout(?string $excludeDomain = null): array;
}
