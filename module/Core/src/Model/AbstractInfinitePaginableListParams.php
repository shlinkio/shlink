<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Paginator\Paginator;

abstract class AbstractInfinitePaginableListParams
{
    private const FIRST_PAGE = 1;

    private int $page;
    private int $itemsPerPage;

    protected function __construct(?int $page, ?int $itemsPerPage)
    {
        $this->page = $this->determinePage($page);
        $this->itemsPerPage = $this->determineItemsPerPage($itemsPerPage);
    }

    private function determinePage(?int $page): int
    {
        return $page === null || $page <= 0 ? self::FIRST_PAGE : $page;
    }

    private function determineItemsPerPage(?int $itemsPerPage): int
    {
        return $itemsPerPage === null || $itemsPerPage < 0 ? Paginator::ALL_ITEMS : $itemsPerPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }
}
