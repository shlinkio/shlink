<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
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

    public static function fromRawData(array $query): self
    {
        $startDate = self::getDateQueryParam($query, 'startDate');
        $endDate = self::getDateQueryParam($query, 'endDate');

        return new self(new DateRange($startDate, $endDate), $query['page'] ?? 1, $query['itemsPerPage'] ?? null);
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

    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    public function hasItemsPerPage(): bool
    {
        return $this->itemsPerPage !== null;
    }
}
