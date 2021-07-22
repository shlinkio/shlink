<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;

use function Functional\map;

class SimpleShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain
    {
        return $domain !== null ? Domain::withAuthority($domain) : null;
    }

    /**
     * @param string[] $tags
     * @return Collection|Tag[]
     */
    public function resolveTags(array $tags): Collections\Collection
    {
        return new Collections\ArrayCollection(map($tags, fn (string $tag) => new Tag($tag)));
    }
}
