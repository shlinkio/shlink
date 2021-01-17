<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;

class SimpleShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain
    {
        return $domain !== null ? new Domain($domain) : null;
    }
}
