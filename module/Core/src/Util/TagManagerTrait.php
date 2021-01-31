<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;

use function Functional\map;

/** @deprecated */
trait TagManagerTrait
{
    /**
     * @param string[] $tags
     * @deprecated
     * @return Collections\Collection|Tag[]
     */
    private function tagNamesToEntities(EntityManagerInterface $em, array $tags): Collections\Collection
    {
        $normalizedTags = ShortUrlInputFilter::withNonRequiredLongUrl([
            ShortUrlInputFilter::TAGS => $tags,
        ])->getValue(ShortUrlInputFilter::TAGS);

        $entities = map($normalizedTags, function (string $tagName) use ($em) {
            $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]) ?? new Tag($tagName);
            $em->persist($tag);

            return $tag;
        });

        return new Collections\ArrayCollection($entities);
    }
}
