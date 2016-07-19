<?php
namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\Visit;
use Zend\Paginator\Paginator;

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
     * Returns the visits on certain shortcode
     *
     * @param $shortCode
     * @return Paginator|Visit[]
     */
    public function info($shortCode);
}
