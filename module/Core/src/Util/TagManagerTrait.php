<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;

trait TagManagerTrait
{
    /**
     * @param EntityManagerInterface $em
     * @param string[] $tags
     * @return Collections\Collection|Tag[]
     */
    protected function tagNamesToEntities(EntityManagerInterface $em, array $tags)
    {
        $entities = [];
        foreach ($tags as $tagName) {
            $tagName = $this->normalizeTagName($tagName);
            $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]) ?: (new Tag())->setName($tagName);
            $em->persist($tag);
            $entities[] = $tag;
        }

        return new Collections\ArrayCollection($entities);
    }

    /**
     * Tag names are trimmed, lower cased and spaces are replaced by dashes
     *
     * @param string $tagName
     * @return string
     */
    protected function normalizeTagName($tagName)
    {
        return str_replace(' ', '-', strtolower(trim($tagName)));
    }
}
