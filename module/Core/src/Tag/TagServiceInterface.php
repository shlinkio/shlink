<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface TagServiceInterface
{
    /**
     * @return Paginator<Tag>
     */
    public function listTags(TagsParams $params, ApiKey|null $apiKey = null): Paginator;

    /**
     * @return Paginator<TagInfo>
     */
    public function tagsInfo(TagsParams $params, ApiKey|null $apiKey = null): Paginator;

    /**
     * @param string[] $tagNames
     * @throws ForbiddenTagOperationException
     */
    public function deleteTags(array $tagNames, ApiKey|null $apiKey = null): void;

    /**
     * @throws TagNotFoundException
     * @throws TagConflictException
     * @throws ForbiddenTagOperationException
     */
    public function renameTag(TagRenaming $renaming, ApiKey|null $apiKey = null): Tag;
}
