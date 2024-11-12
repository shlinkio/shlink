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
use Shlinkio\Shlink\Core\Tag\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class TagService implements TagServiceInterface
{
    public function __construct(private ORM\EntityManagerInterface $em, private TagRepositoryInterface $repo)
    {
    }

    /**
     * @inheritDoc
     */
    public function listTags(TagsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        return $this->createPaginator(new TagsPaginatorAdapter($this->repo, $params, $apiKey), $params);
    }

    /**
     * @inheritDoc
     */
    public function tagsInfo(TagsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        return $this->createPaginator(new TagsInfoPaginatorAdapter($this->repo, $params, $apiKey), $params);
    }

    /**
     * @template T
     * @param AdapterInterface<T> $adapter
     * @return Paginator<T>
     */
    private function createPaginator(AdapterInterface $adapter, TagsParams $params): Paginator
    {
        $paginator = new Paginator($adapter);
        $paginator->setMaxPerPage($params->itemsPerPage)
                  ->setCurrentPage($params->page);

        return $paginator;
    }

    /**
     * @inheritDoc
     */
    public function deleteTags(array $tagNames, ApiKey|null $apiKey = null): void
    {
        if (ApiKey::isShortUrlRestricted($apiKey)) {
            throw ForbiddenTagOperationException::forDeletion();
        }

        $this->repo->deleteByName($tagNames);
    }

    /**
     * @inheritDoc
     */
    public function renameTag(Renaming $renaming, ApiKey|null $apiKey = null): Tag
    {
        if (ApiKey::isShortUrlRestricted($apiKey)) {
            throw ForbiddenTagOperationException::forRenaming();
        }

        $tag = $this->repo->findOneBy(['name' => $renaming->oldName]);
        if ($tag === null) {
            throw TagNotFoundException::fromTag($renaming->oldName);
        }

        $newNameExists = $renaming->nameChanged() && $this->repo->count(['name' => $renaming->newName]) > 0;
        if ($newNameExists) {
            throw TagConflictException::forExistingTag($renaming);
        }

        $tag->rename($renaming->newName);
        $this->em->flush();

        return $tag;
    }
}
