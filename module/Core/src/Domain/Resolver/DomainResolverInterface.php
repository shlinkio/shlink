<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;

interface DomainResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain;
}
