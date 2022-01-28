<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

use function Shlinkio\Shlink\Core\parseDateRangeFromQuery;

final class VisitsParams extends AbstractInfinitePaginableListParams
{
    private DateRange $dateRange;

    public function __construct(
        ?DateRange $dateRange = null,
        ?int $page = null,
        ?int $itemsPerPage = null,
        private bool $excludeBots = false,
    ) {
        parent::__construct($page, $itemsPerPage);
        $this->dateRange = $dateRange ?? DateRange::emptyInstance();
    }

    public static function fromRawData(array $query): self
    {
        return new self(
            parseDateRangeFromQuery($query, 'startDate', 'endDate'),
            isset($query['page']) ? (int) $query['page'] : null,
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
            isset($query['excludeBots']),
        );
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function excludeBots(): bool
    {
        return $this->excludeBots;
    }
}
