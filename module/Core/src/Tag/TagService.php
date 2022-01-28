<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Doctrine\ORM;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsInfoPaginatorAdapter;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagService implements TagServiceInterface
{
    public function __construct(private ORM\EntityManagerInterface $em)
    {
    }

    /**
     * @return Tag[]|Paginator
     */
    public function listTags(TagsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $this->createPaginator(new TagsPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    /**
     * @return TagInfo[]|Paginator
     */
    public function tagsInfo(TagsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var TagRepositoryInterface $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $this->createPaginator(new TagsInfoPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    private function createPaginator(AdapterInterface $adapter, TagsParams $params): Paginator
    {
        return (new Paginator($adapter))
            ->setMaxPerPage($params->getItemsPerPage())
            ->setCurrentPage($params->getPage());
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
