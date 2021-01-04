<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagService implements TagServiceInterface
{
    use TagManagerTrait;

    private ORM\EntityManagerInterface $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Tag[]
     */
    public function listTags(?ApiKey $apiKey = null): array
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        /** @var Tag[] $tags */
        $tags = $repo->match(Spec::andX(
            Spec::orderBy('name'),
            new WithApiKeySpecsEnsuringJoin($apiKey),
        ));
        return $tags;
    }

    /**
     * @return TagInfo[]
     */
    public function tagsInfo(?ApiKey $apiKey = null): array
    {
        /** @var TagRepositoryInterface $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $repo->findTagsWithInfo($apiKey !== null ? $apiKey->spec() : null);
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
     * @deprecated
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
