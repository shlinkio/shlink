<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Doctrine\ORM;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsInfoPaginatorAdapter;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class TagService implements TagServiceInterface
{
    public function __construct(private ORM\EntityManagerInterface $em)
    {
    }

    /**
     * @inheritDoc
     */
    public function listTags(TagsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $this->createPaginator(new TagsPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    /**
     * @inheritDoc
     */
    public function tagsInfo(TagsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var TagRepositoryInterface $repo */
        $repo = $this->em->getRepository(Tag::class);
        return $this->createPaginator(new TagsInfoPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    /**
     * @template T
     * @param AdapterInterface<T> $adapter
     * @return Paginator<T>
     */
    private function createPaginator(AdapterInterface $adapter, TagsParams $params): Paginator
    {
        return (new Paginator($adapter))
            ->setMaxPerPage($params->itemsPerPage)
            ->setCurrentPage($params->page);
    }

    /**
     * @inheritDoc
     */
    public function deleteTags(array $tagNames, ApiKey|null $apiKey = null): void
    {
        if (ApiKey::isShortUrlRestricted($apiKey)) {
            throw ForbiddenTagOperationException::forDeletion();
        }

        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        $repo->deleteByName($tagNames);
    }

    /**
     * @inheritDoc
     */
    public function renameTag(Renaming $renaming, ApiKey|null $apiKey = null): Tag
    {
        if (ApiKey::isShortUrlRestricted($apiKey)) {
            throw ForbiddenTagOperationException::forRenaming();
        }

        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);

        /** @var Tag|null $tag */
        $tag = $repo->findOneBy(['name' => $renaming->oldName]);
        if ($tag === null) {
            throw TagNotFoundException::fromTag($renaming->oldName);
        }

        $newNameExists = $renaming->nameChanged() && $repo->count(['name' => $renaming->newName]) > 0;
        if ($newNameExists) {
            throw TagConflictException::forExistingTag($renaming);
        }

        $tag->rename($renaming->newName);
        $this->em->flush();

        return $tag;
    }
}
