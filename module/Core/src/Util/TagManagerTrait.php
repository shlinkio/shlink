<?php
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
            $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]) ?: (new Tag())->setName($tagName);
            $em->persist($tag);
            $entities[] = $tag;
        }

        return new Collections\ArrayCollection($entities);
    }
}
