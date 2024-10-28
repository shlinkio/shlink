<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

use function array_map;

class SimpleShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    public function resolveDomain(string|null $domain): Domain|null
    {
        return $domain !== null ? Domain::withAuthority($domain) : null;
    }

    /**
     * @param string[] $tags
     * @return Collections\Collection<int, Tag>
     */
    public function resolveTags(array $tags): Collections\Collection
    {
        return new Collections\ArrayCollection(array_map(fn (string $tag) => new Tag($tag), $tags));
    }
}
