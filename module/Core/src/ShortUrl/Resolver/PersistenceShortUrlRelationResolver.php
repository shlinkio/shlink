<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\Collections;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;

use function Functional\map;

class PersistenceShortUrlRelationResolver implements ShortUrlRelationResolverInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function resolveDomain(?string $domain): ?Domain
    {
        if ($domain === null) {
            return null;
        }

        /** @var Domain|null $existingDomain */
        $existingDomain = $this->em->getRepository(Domain::class)->findOneBy(['authority' => $domain]);
        return $existingDomain ?? new Domain($domain);
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

        $repo = $this->em->getRepository(Tag::class);
        return new Collections\ArrayCollection(map($tags, function (string $tagName) use ($repo): Tag {
            $tag = $repo->findOneBy(['name' => $tagName]) ?? new Tag($tagName);
            $this->em->persist($tag);

            return $tag;
        }));
    }
}
