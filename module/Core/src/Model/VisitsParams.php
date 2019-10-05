<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Util\DateRange;

final class VisitsParams
{
    private const FIRST_PAGE = 1;
    private const ALL_ITEMS = -1;

    /** @var null|DateRange */
    private $dateRange;
    /** @var int */
    private $page;
    /** @var int */
    private $itemsPerPage;

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
        $startDate = self::getDateQueryParam($query, 'startDate');
        $endDate = self::getDateQueryParam($query, 'endDate');

        return new self(
            new DateRange($startDate, $endDate),
            (int) ($query['page'] ?? 1),
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null
        );
    }

    private static function getDateQueryParam(array $query, string $key): ?Chronos
    {
        return ! isset($query[$key]) || empty($query[$key]) ? null : Chronos::parse($query[$key]);
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
