<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Matomo\Model\SendVisitsResult;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface MatomoVisitSenderInterface
{
    /**
     * Sends all visits in provided date range to matomo, and returns the amount of affected visits
     */
    public function sendVisitsInDateRange(
        DateRange $dateRange,
        VisitSendingProgressTrackerInterface|null $progressTracker = null,
    ): SendVisitsResult;

    public function sendVisit(Visit $visit, string|null $originalIpAddress = null): void;
}
