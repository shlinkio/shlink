<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
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
    private function tagNamesToEntities(EntityManagerInterface $em, array $tags)
    {
        $entities = [];
        foreach ($tags as $tagName) {
            $tagName = $this->normalizeTagName($tagName);
            $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]) ?: new Tag($tagName);
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
    private function normalizeTagName($tagName): string
    {
        return str_replace(' ', '-', strtolower(trim($tagName)));
    }
}
