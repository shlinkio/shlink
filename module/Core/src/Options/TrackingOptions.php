<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use function array_key_exists;

final class TrackingOptions
{
    public function __construct(
        public readonly bool $anonymizeRemoteAddr = true,
        public readonly bool $trackOrphanVisits = true,
        public readonly ?string $disableTrackParam = null,
        public readonly bool $disableTracking = false,
        public readonly bool $disableIpTracking = false,
        public readonly bool $disableReferrerTracking = false,
        public readonly bool $disableUaTracking = false,
        /** @var string[] */
        public readonly array $disableTrackingFrom = [],
    ) {
    }

    public function hasDisableTrackingFrom(): bool
    {
        return ! empty($this->disableTrackingFrom);
    }

    public function queryHasDisableTrackParam(array $query): bool
    {
        return $this->disableTrackParam !== null && array_key_exists($this->disableTrackParam, $query);
    }
}
