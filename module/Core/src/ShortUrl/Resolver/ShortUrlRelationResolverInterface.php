<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain;

    public function resolveApiKey(?string $key): ?ApiKey;
}
