<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

use function Functional\map;

class SimpleShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    public function resolveDomain(?string $domain): ?Domain
    {
        return $domain !== null ? Domain::withAuthority($domain) : null;
    }

    /**
     * @param string[] $tags
     * @return Collections\Collection<int, Tag>
     */
    public function resolveTags(array $tags): Collections\Collection
    {
        return new Collections\ArrayCollection(map($tags, fn (string $tag) => new Tag($tag)));
    }
}
