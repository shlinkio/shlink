<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

use function Shlinkio\Shlink\Core\parseDateFromQuery;

final class VisitsParams
{
    private const FIRST_PAGE = 1;
    private const ALL_ITEMS = -1;

    private ?DateRange $dateRange;
    private int $page;
    private int $itemsPerPage;

    public function __construct(?DateRange $dateRange = null, int $page = self::FIRST_PAGE, ?int $itemsPerPage = null)
    {
        $this->dateRange = $dateRange ?? new DateRange();
        $this->page = $page;
        $this->itemsPerPage = $this->determineItemsPerPage($itemsPerPage);
    }

    private function determineItemsPerPage(?int $itemsPerPage): int
    {
        if ($itemsPerPage !== null && $itemsPerPage < 0) {
            return self::ALL_ITEMS;
        }

        return $itemsPerPage ?? self::ALL_ITEMS;
    }

    public static function fromRawData(array $query): self
    {
        return new self(
            new DateRange(parseDateFromQuery($query, 'startDate'), parseDateFromQuery($query, 'endDate')),
            (int) ($query['page'] ?? 1),
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
        );
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
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
