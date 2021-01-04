<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Shlinkio\Shlink\Core\Entity\Domain;

interface ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain;
}
