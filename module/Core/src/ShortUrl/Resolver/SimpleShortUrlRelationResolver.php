<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class SimpleShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain
    {
        return $domain !== null ? new Domain($domain) : null;
    }

    public function resolveApiKey(?string $key): ?ApiKey
    {
        return null;
    }
}
