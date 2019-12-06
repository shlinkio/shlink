<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\Tag;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
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
     * @param array $tagNames
     * @return void
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
     * @param string $oldName
     * @param string $newName
     * @return Tag
     * @throws EntityDoesNotExistException
     * @throws TagConflictException
     * @throws ORM\OptimisticLockException
     */
    public function renameTag($oldName, $newName): Tag
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        $criteria = ['name' => $oldName];

        /** @var Tag|null $tag */
        $tag = $repo->findOneBy($criteria);
        if ($tag === null) {
            throw EntityDoesNotExistException::createFromEntityAndConditions(Tag::class, $criteria);
        }

        $newNameExists = $newName !== $oldName && $repo->count(['name' => $newName]) > 0;
        if ($newNameExists) {
            throw TagConflictException::fromExistingTag($newName);
        }

        $tag->rename($newName);

        /** @var ORM\EntityManager $em */
        $em = $this->em;
        $em->flush($tag);

        return $tag;
    }
}
