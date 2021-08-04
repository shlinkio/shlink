<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;

use function Functional\map;
use function Functional\unique;

class PersistenceShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    /** @var array<string, Domain> */
    private array $memoizedNewDomains = [];
    /** @var array<string, Tag> */
    private array $memoizedNewTags = [];

    public function __construct(private EntityManagerInterface $em)
    {
        $this->em->getEventManager()->addEventListener(Events::postFlush, $this);
    }

    public function resolveDomain(?string $domain): ?Domain
    {
        if ($domain === null) {
            return null;
        }

        /** @var Domain|null $existingDomain */
        $existingDomain = $this->em->getRepository(Domain::class)->findOneBy(['authority' => $domain]);

        // Memoize only new domains, and let doctrine handle objects hydrated from persistence
        return $existingDomain ?? $this->memoizeNewDomain($domain);
    }

    private function memoizeNewDomain(string $domain): Domain
    {
        return $this->memoizedNewDomains[$domain] = $this->memoizedNewDomains[$domain] ?? Domain::withAuthority(
            $domain,
        );
    }

    /**
     * @param string[] $tags
     * @return Collection|Tag[]
     */
    public function resolveTags(array $tags): Collections\Collection
    {
        if (empty($tags)) {
            return new Collections\ArrayCollection();
        }

        $tags = unique($tags);
        $repo = $this->em->getRepository(Tag::class);

        return new Collections\ArrayCollection(map($tags, function (string $tagName) use ($repo): Tag {
            // Memoize only new tags, and let doctrine handle objects hydrated from persistence
            $tag = $repo->findOneBy(['name' => $tagName])  ?? $this->memoizeNewTag($tagName);
            $this->em->persist($tag);

            return $tag;
        }));
    }

    private function memoizeNewTag(string $tagName): Tag
    {
        return $this->memoizedNewTags[$tagName] = $this->memoizedNewTags[$tagName] ?? new Tag($tagName);
    }

    public function postFlush(): void
    {
        $this->memoizedNewDomains = [];
        $this->memoizedNewTags = [];
    }
}
