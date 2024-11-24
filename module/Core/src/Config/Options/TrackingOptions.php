<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

use function array_key_exists;
use function Shlinkio\Shlink\Core\splitByComma;

final readonly class TrackingOptions
{
    /**
     * @param string[] $disableTrackingFrom
     */
    public function __construct(
        // Tells if IP addresses should be anonymized before persisting, to fulfil data protection regulations.
        // This applies only if IP address tracking is enabled
        public bool $anonymizeRemoteAddr = true,
        // Tells if visits to not-found URLs should be tracked. The disableTracking option takes precedence
        public bool $trackOrphanVisits = true,
        // A query param that, if provided, will disable tracking of one particular visit. Always takes precedence over
        // other options
        public string|null $disableTrackParam = null,
        // If true, visits will not be tracked at all
        public bool $disableTracking = false,
        // If true, visits will be tracked, but neither the IP address, nor the location will be resolved
        public bool $disableIpTracking = false,
        // If true, the referrers will not be tracked
        public bool $disableReferrerTracking = false,
        // If true, the user agent will not be tracked
        public bool $disableUaTracking = false,
        // A list of IP addresses, patterns or CIDR blocks from which tracking is disabled by default
        public array $disableTrackingFrom = [],
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            anonymizeRemoteAddr: (bool) EnvVars::ANONYMIZE_REMOTE_ADDR->loadFromEnv(),
            trackOrphanVisits: (bool) EnvVars::TRACK_ORPHAN_VISITS->loadFromEnv(),
            disableTrackParam: EnvVars::DISABLE_TRACK_PARAM->loadFromEnv(),
            disableTracking: (bool) EnvVars::DISABLE_TRACKING->loadFromEnv(),
            disableIpTracking: (bool) EnvVars::DISABLE_IP_TRACKING->loadFromEnv(),
            disableReferrerTracking: (bool) EnvVars::DISABLE_REFERRER_TRACKING->loadFromEnv(),
            disableUaTracking: (bool) EnvVars::DISABLE_UA_TRACKING->loadFromEnv(),
            disableTrackingFrom: splitByComma(EnvVars::DISABLE_TRACKING_FROM->loadFromEnv()),
        );
    }

    public function hasDisableTrackingFrom(): bool
    {
        return ! empty($this->disableTrackingFrom);
    }

    public function queryHasDisableTrackParam(array $query): bool
    {
        return $this->disableTrackParam !== null && array_key_exists($this->disableTrackParam, $query);
    }

    /**
     * If IP address tracking is disabled, or tracking is disabled all together, then geolocation is not relevant
     */
    public function isGeolocationRelevant(): bool
    {
        return ! $this->disableTracking && ! $this->disableIpTracking;
    }
}
