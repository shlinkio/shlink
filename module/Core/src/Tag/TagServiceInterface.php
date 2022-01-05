<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface TagServiceInterface
{
    /**
     * @return Tag[]|Paginator
     */
    public function listTags(TagsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @return TagInfo[]|Paginator
     */
    public function tagsInfo(TagsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @param string[] $tagNames
     * @throws ForbiddenTagOperationException
     */
    public function deleteTags(array $tagNames, ?ApiKey $apiKey = null): void;

    /**
     * @throws TagNotFoundException
     * @throws TagConflictException
     * @throws ForbiddenTagOperationException
     */
    public function renameTag(TagRenaming $renaming, ?ApiKey $apiKey = null): Tag;
}
