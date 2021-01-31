<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;

interface ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain;

    /**
     * @param string[] $tags
     * @return Collection|Tag[]
     */
    public function resolveTags(array $tags): Collection;
}
