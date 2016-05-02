<?php
namespace Acelaya\UrlShortener\Service;

interface VisitsTrackerInterface
{
    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param array $visitorData Defaults to global $_SERVER
     */
    public function track($shortCode, array $visitorData = null);
}
