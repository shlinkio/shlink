<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

class TagsInfoPaginatorAdapter extends AbstractTagsPaginatorAdapter
{
    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findTagsWithInfo($length, $offset, null, $this->apiKey);
    }
}
