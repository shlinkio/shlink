<?php
namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitsTrackerInterface
{
    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param array $visitorData Defaults to global $_SERVER
     */
    public function track($shortCode, array $visitorData = null);

    /**
     * Returns the visits on certain short code
     *
     * @param $shortCode
     * @param DateRange $dateRange
     * @return Visit[]
     */
    public function info($shortCode, DateRange $dateRange = null);
}
