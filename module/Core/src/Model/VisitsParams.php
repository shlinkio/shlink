<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

final class VisitsParams
{
    /** @var null|DateRange */
    private $dateRange;
    /** @var int */
    private $page = 1;
    /** @var null|int */
    private $itemsPerPage;

    public function __construct(?DateRange $dateRange = null, int $page = 1, ?int $itemsPerPage = null)
    {
        $this->dateRange = $dateRange ?? new DateRange();
        $this->page = $page;
        $this->itemsPerPage = $itemsPerPage;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    public function hasItemsPerPage(): bool
    {
        return $this->itemsPerPage !== null;
    }
}
