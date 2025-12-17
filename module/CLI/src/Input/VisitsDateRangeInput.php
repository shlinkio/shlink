<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Shlinkio\Shlink\Common\Util\DateRange;
use Symfony\Component\Console\Attribute\Option;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

class VisitsDateRangeInput
{
    #[Option('Only return visits older than this date', shortcut: 's')]
    public string|null $startDate = null;

    #[Option('Only return visits newer than this date', shortcut: 'e')]
    public string|null $endDate = null;

    public function toDateRange(): DateRange
    {
        return buildDateRange(
            startDate: normalizeOptionalDate($this->startDate),
            endDate: normalizeOptionalDate($this->endDate),
        );
    }
}
