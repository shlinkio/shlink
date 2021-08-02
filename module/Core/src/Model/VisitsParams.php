<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

use function Shlinkio\Shlink\Core\parseDateRangeFromQuery;

final class VisitsParams
{
    private const FIRST_PAGE = 1;
    private const ALL_ITEMS = -1;

    private ?DateRange $dateRange;
    private int $page;
    private int $itemsPerPage;
    private bool $excludeBots;

    public function __construct(
        ?DateRange $dateRange = null,
        int $page = self::FIRST_PAGE,
        ?int $itemsPerPage = null,
        bool $excludeBots = false
    ) {
        $this->dateRange = $dateRange ?? new DateRange();
        $this->page = $this->determinePage($page);
        $this->itemsPerPage = $this->determineItemsPerPage($itemsPerPage);
        $this->excludeBots = $excludeBots;
    }

    private function determinePage(int $page): int
    {
        return $page > 0 ? $page : self::FIRST_PAGE;
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
            parseDateRangeFromQuery($query, 'startDate', 'endDate'),
            (int) ($query['page'] ?? self::FIRST_PAGE),
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
            isset($query['excludeBots']),
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

    public function excludeBots(): bool
    {
        return $this->excludeBots;
    }
}
