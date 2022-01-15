<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use function array_key_exists;
use function explode;
use function Functional\map;
use function is_array;
use function trim;

class TrackingOptions extends AbstractOptions
{
    private bool $anonymizeRemoteAddr = true;
    private bool $trackOrphanVisits = true;
    private ?string $disableTrackParam = null;
    private bool $disableTracking = false;
    private bool $disableIpTracking = false;
    private bool $disableReferrerTracking = false;
    private bool $disableUaTracking = false;
    private array $disableTrackingFrom = [];

    public function anonymizeRemoteAddr(): bool
    {
        return $this->anonymizeRemoteAddr;
    }

    protected function setAnonymizeRemoteAddr(bool $anonymizeRemoteAddr): void
    {
        $this->anonymizeRemoteAddr = $anonymizeRemoteAddr;
    }

    public function trackOrphanVisits(): bool
    {
        return $this->trackOrphanVisits;
    }

    protected function setTrackOrphanVisits(bool $trackOrphanVisits): void
    {
        $this->trackOrphanVisits = $trackOrphanVisits;
    }

    public function getDisableTrackParam(): ?string
    {
        return $this->disableTrackParam;
    }

    public function queryHasDisableTrackParam(array $query): bool
    {
        return $this->disableTrackParam !== null && array_key_exists($this->disableTrackParam, $query);
    }

    protected function setDisableTrackParam(?string $disableTrackParam): void
    {
        $this->disableTrackParam = $disableTrackParam;
    }

    public function disableTracking(): bool
    {
        return $this->disableTracking;
    }

    protected function setDisableTracking(bool $disableTracking): void
    {
        $this->disableTracking = $disableTracking;
    }

    public function disableIpTracking(): bool
    {
        return $this->disableIpTracking;
    }

    protected function setDisableIpTracking(bool $disableIpTracking): void
    {
        $this->disableIpTracking = $disableIpTracking;
    }

    public function disableReferrerTracking(): bool
    {
        return $this->disableReferrerTracking;
    }

    protected function setDisableReferrerTracking(bool $disableReferrerTracking): void
    {
        $this->disableReferrerTracking = $disableReferrerTracking;
    }

    public function disableUaTracking(): bool
    {
        return $this->disableUaTracking;
    }

    protected function setDisableUaTracking(bool $disableUaTracking): void
    {
        $this->disableUaTracking = $disableUaTracking;
    }

    public function disableTrackingFrom(): array
    {
        return $this->disableTrackingFrom;
    }

    public function hasDisableTrackingFrom(): bool
    {
        return ! empty($this->disableTrackingFrom);
    }

    protected function setDisableTrackingFrom(string|array|null $disableTrackingFrom): void
    {
        $this->disableTrackingFrom = match (true) {
            is_array($disableTrackingFrom) => $disableTrackingFrom,
            $disableTrackingFrom === null => [],
            default => map(explode(',', $disableTrackingFrom), static fn (string $value) => trim($value)),
        };
    }
}
