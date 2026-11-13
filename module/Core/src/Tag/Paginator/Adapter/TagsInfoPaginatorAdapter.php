<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

use Override;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;

/** @extends AbstractTagsPaginatorAdapter<TagInfo> */
class TagsInfoPaginatorAdapter extends AbstractTagsPaginatorAdapter
{
    #[Override]
    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findTagsWithInfo(
            TagsListFiltering::fromRangeAndParams($length, $offset, $this->params, $this->apiKey),
        );
    }
}
