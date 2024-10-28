<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Paginator\Paginator;

abstract class AbstractInfinitePaginableListParams
{
    private const FIRST_PAGE = 1;

    public readonly int $page;
    public readonly int $itemsPerPage;

    protected function __construct(int|null $page, int|null $itemsPerPage)
    {
        $this->page = $this->determinePage($page);
        $this->itemsPerPage = $this->determineItemsPerPage($itemsPerPage);
    }

    private function determinePage(int|null $page): int
    {
        return $page === null || $page <= 0 ? self::FIRST_PAGE : $page;
    }

    private function determineItemsPerPage(int|null $itemsPerPage): int
    {
        return $itemsPerPage === null || $itemsPerPage < 0 ? Paginator::ALL_ITEMS : $itemsPerPage;
    }
}
