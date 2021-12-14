<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Doctrine\ORM;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagService implements TagServiceInterface
{
    public function __construct(private ORM\EntityManagerInterface $em)
    {
    }

    /**
     * @return Tag[]
     */
    public function listTags(?ApiKey $apiKey = null): array
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $repo->match(Spec::andX(
            Spec::orderBy('name'),
            new WithApiKeySpecsEnsuringJoin($apiKey),
        ));
    }

    /**
     * @return TagInfo[]
     */
    public function tagsInfo(?ApiKey $apiKey = null): array
    {
        /** @var TagRepositoryInterface $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $repo->findTagsWithInfo($apiKey);
    }

    /**
     * @param string[] $tagNames
     * @throws ForbiddenTagOperationException
     */
    public function deleteTags(array $tagNames, ?ApiKey $apiKey = null): void
    {
        if ($apiKey !== null && ! $apiKey->isAdmin()) {
            throw ForbiddenTagOperationException::forDeletion();
        }

        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        $repo->deleteByName($tagNames);
    }

    /**
     * @throws TagNotFoundException
     * @throws TagConflictException
     * @throws ForbiddenTagOperationException
     */
    public function renameTag(TagRenaming $renaming, ?ApiKey $apiKey = null): Tag
    {
        if ($apiKey !== null && ! $apiKey->isAdmin()) {
            throw ForbiddenTagOperationException::forRenaming();
        }

        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);

        /** @var Tag|null $tag */
        $tag = $repo->findOneBy(['name' => $renaming->oldName()]);
        if ($tag === null) {
            throw TagNotFoundException::fromTag($renaming->oldName());
        }

        $newNameExists = $renaming->nameChanged() && $repo->count(['name' => $renaming->newName()]) > 0;
        if ($newNameExists) {
            throw TagConflictException::forExistingTag($renaming);
        }

        $tag->rename($renaming->newName());
        $this->em->flush();

        return $tag;
    }
}
