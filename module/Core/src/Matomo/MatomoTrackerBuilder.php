<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use MatomoTracker;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

readonly class MatomoTrackerBuilder implements MatomoTrackerBuilderInterface
{
    public const MATOMO_DEFAULT_TIMEOUT = 10; // Time in seconds

    public function __construct(private MatomoOptions $options)
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
        $tracker
            // Token required to set the IP and location
            ->setTokenAuth($this->options->apiToken)
            // Ensure params are not sent in the URL, for security reasons
            ->setRequestMethodNonBulk('POST')
            // Set a reasonable timeout
            ->setRequestTimeout(self::MATOMO_DEFAULT_TIMEOUT)
            ->setRequestConnectTimeout(self::MATOMO_DEFAULT_TIMEOUT);

        // We don't want to bulk send, as every request to Shlink will create a new tracker
        $tracker->disableBulkTracking();
        // Disable cookies, as they are ignored anyway
        $tracker->disableCookieSupport();

        return $tracker;
    }
}
