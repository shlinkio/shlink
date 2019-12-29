<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\Tag;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;

class TagService implements TagServiceInterface
{
    use TagManagerTrait;

    /** @var ORM\EntityManagerInterface */
    private $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Tag[]
     * @throws \UnexpectedValueException
     */
    public function listTags(): array
    {
        /** @var Tag[] $tags */
        $tags = $this->em->getRepository(Tag::class)->findBy([], ['name' => 'ASC']);
        return $tags;
    }

    /**
     * @param string[] $tagNames
     */
    public function deleteTags(array $tagNames): void
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        $repo->deleteByName($tagNames);
    }

    /**
     * Provided a list of tag names, creates all that do not exist yet
     *
     * @param string[] $tagNames
     * @return Collection|Tag[]
     */
    public function createTags(array $tagNames): Collection
    {
        $tags = $this->tagNamesToEntities($this->em, $tagNames);
        $this->em->flush();

        return $tags;
    }

    /**
     * @throws TagNotFoundException
     * @throws TagConflictException
     */
    public function renameTag(string $oldName, string $newName): Tag
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);

        /** @var Tag|null $tag */
        $tag = $repo->findOneBy(['name' => $oldName]);
        if ($tag === null) {
            throw TagNotFoundException::fromTag($oldName);
        }

        $newNameExists = $newName !== $oldName && $repo->count(['name' => $newName]) > 0;
        if ($newNameExists) {
            throw TagConflictException::fromExistingTag($oldName, $newName);
        }

        $tag->rename($newName);
        $this->em->flush();

        return $tag;
    }
}
