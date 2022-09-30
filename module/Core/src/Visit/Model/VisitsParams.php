<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\AbstractInfinitePaginableListParams;

use function Shlinkio\Shlink\Core\parseDateRangeFromQuery;

final class VisitsParams extends AbstractInfinitePaginableListParams
{
    public readonly DateRange $dateRange;

    public function __construct(
        ?DateRange $dateRange = null,
        ?int $page = null,
        ?int $itemsPerPage = null,
        public readonly bool $excludeBots = false,
    ) {
        parent::__construct($page, $itemsPerPage);
        $this->dateRange = $dateRange ?? DateRange::allTime();
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
}
