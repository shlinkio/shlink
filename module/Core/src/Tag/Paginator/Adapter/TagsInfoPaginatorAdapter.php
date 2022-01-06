<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;

class TagsInfoPaginatorAdapter extends AbstractTagsPaginatorAdapter
{
    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findTagsWithInfo(
            new TagsListFiltering($length, $offset, $this->params->searchTerm(), $this->apiKey),
        );
    }
}
