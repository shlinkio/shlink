<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;

class SimpleDomainResolver implements DomainResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain
    {
        return $domain !== null ? new Domain($domain) : null;
    }
}
