<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

use function Functional\invoke;
use function Functional\map;
use function Functional\unique;

class PersistenceShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    /** @var array<string, Domain> */
    private array $memoizedNewDomains = [];
    /** @var array<string, Tag> */
    private array $memoizedNewTags = [];
    /** @var array<string, Lock> */
    private array $tagLocks = [];
    /** @var array<string, Lock> */
    private array $domainLocks = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlShortenerOptions $options = new UrlShortenerOptions(),
        private readonly LockFactory $locker = new LockFactory(new InMemoryStore()),
    ) {
        // Registering this as an event listener will make the postFlush method to be called automatically
        $this->em->getEventManager()->addEventListener(Events::postFlush, $this);
    }

    public function resolveDomain(?string $domain): ?Domain
    {
        if ($domain === null || $domain === $this->options->defaultDomain()) {
            return null;
        }

        $this->lock($this->domainLocks, 'domain_' . $domain);

        /** @var Domain|null $existingDomain */
        $existingDomain = $this->em->getRepository(Domain::class)->findOneBy(['authority' => $domain]);
        if ($existingDomain) {
            // The lock can be released immediately of the domain is not new
            $this->releaseLock($this->domainLocks, 'domain_' . $domain);
            return $existingDomain;
        }

        // Memoize only new domains, and let doctrine handle objects hydrated from persistence
        return $this->memoizeNewDomain($domain);
    }

    private function memoizeNewDomain(string $domain): Domain
    {
        return $this->memoizedNewDomains[$domain] ??= Domain::withAuthority($domain);
    }

    /**
     * @param string[] $tags
     * @return Collection<int, Tag>
     */
    public function resolveTags(array $tags): Collections\Collection
    {
        if (empty($tags)) {
            return new Collections\ArrayCollection();
        }

        $tags = unique($tags);
        $repo = $this->em->getRepository(Tag::class);

        return new Collections\ArrayCollection(map($tags, function (string $tagName) use ($repo): Tag {
            $this->lock($this->tagLocks, 'tag_' . $tagName);

            $existingTag = $repo->findOneBy(['name' => $tagName]);
            if ($existingTag) {
                $this->releaseLock($this->tagLocks, 'tag_' . $tagName);
                return $existingTag;
            }

            // Memoize only new tags, and let doctrine handle objects hydrated from persistence
            $tag = $this->memoizeNewTag($tagName);
            $this->em->persist($tag);

            return $tag;
        }));
    }

    private function memoizeNewTag(string $tagName): Tag
    {
        return $this->memoizedNewTags[$tagName] ??= new Tag($tagName);
    }

    /**
     * @param array<string, Lock> $locks
     */
    private function lock(array &$locks, string $name): void
    {
        // Lock dependency creation for up to 5 seconds. This will prevent errors when trying to create the same one
        // more than once in parallel.
        $locks[$name] = $lock = $this->locker->createLock($name, 5);
        $lock->acquire(true);
    }

    /**
     * @param array<string, Lock> $locks
     */
    private function releaseLock(array &$locks, string $name): void
    {
        $locks[$name]->release();
        unset($locks[$name]);
    }

    public function postFlush(): void
    {
        // Reset memoized domains and tags
        $this->memoizedNewDomains = [];
        $this->memoizedNewTags = [];

        // Release all locks
        invoke($this->tagLocks, 'release');
        invoke($this->domainLocks, 'release');
        $this->tagLocks = [];
        $this->domainLocks = [];
    }
}
