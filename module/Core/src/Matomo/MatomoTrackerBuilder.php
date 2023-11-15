<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use MatomoTracker;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

class MatomoTrackerBuilder implements MatomoTrackerBuilderInterface
{
    public function __construct(private readonly MatomoOptions $options)
    {
    }

    /**
     * @throws RuntimeException If there's any missing matomo parameter
     */
    public function buildMatomoTracker(): MatomoTracker
    {
        $siteId = $this->options->siteId();
        if ($siteId === null || $this->options->baseUrl === null || $this->options->apiToken === null) {
            throw new RuntimeException(
                'Cannot create MatomoTracker. Either site ID, base URL or api token are not defined',
            );
        }

        // Create a new MatomoTracker on every request, because it infers request info during construction
        $tracker = new MatomoTracker($siteId, $this->options->baseUrl);
        // Token required to set the IP and location
        $tracker->setTokenAuth($this->options->apiToken);
        // We don't want to bulk send, as every request to Shlink will create a new tracker
        $tracker->disableBulkTracking();
        // Ensure params are not sent in the URL, for security reasons
        $tracker->setRequestMethodNonBulk('POST');

        return $tracker;
    }
}
