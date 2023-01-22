<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

interface ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain;

    /**
     * @param string[] $tags
     * @return Collection<int, Tag>
     */
    public function resolveTags(array $tags): Collection;
}
