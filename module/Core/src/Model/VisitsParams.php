<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

use function Shlinkio\Shlink\Core\parseDateRangeFromQuery;

final class VisitsParams
{
    private const FIRST_PAGE = 1;
    private const ALL_ITEMS = -1;

    private DateRange $dateRange;
    private int $itemsPerPage;

    public function __construct(
        ?DateRange $dateRange = null,
        private int $page = self::FIRST_PAGE,
        ?int $itemsPerPage = null,
        private bool $excludeBots = false
    ) {
        $this->dateRange = $dateRange ?? new DateRange();
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
            parseDateRangeFromQuery($query, 'startDate', 'endDate'),
            (int) ($query['page'] ?? 1),
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
