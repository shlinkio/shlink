<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\Visit;

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
     * @return Visit[]
     */
    public function info($shortCode);
}
