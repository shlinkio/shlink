<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;

use function Functional\map;
use function str_replace;
use function strtolower;
use function trim;

trait TagManagerTrait
{
    /**
     * @param EntityManagerInterface $em
     * @param string[] $tags
     * @return Collections\Collection|Tag[]
     */
    private function tagNamesToEntities(EntityManagerInterface $em, array $tags): Collections\Collection
    {
        $entities = map($tags, function (string $tagName) use ($em): Tag {
            $tagName = $this->normalizeTagName($tagName);
            $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]) ?? new Tag($tagName);
            $em->persist($tag);

            return $tag;
        });

        return new Collections\ArrayCollection($entities);
    }

    private function normalizeTagName(string $tagName): string
    {
        return str_replace(' ', '-', strtolower(trim($tagName)));
    }
}
