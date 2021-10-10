<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\IpGeolocation\Exception\RuntimeException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\LockFactory;

use function is_int;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const LOCK_NAME = 'geolocation-db-update';

    public function __construct(
        private DbUpdaterInterface $dbUpdater,
        private Reader $geoLiteDbReader,
        private LockFactory $locker,
        private TrackingOptions $trackingOptions,
    ) {
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(?callable $beforeDownload = null, ?callable $handleProgress = null): void
    {
        if ($this->trackingOptions->disableTracking() || $this->trackingOptions->disableIpTracking()) {
            return;
        }

        $lock = $this->locker->createLock(self::LOCK_NAME);
        $lock->acquire(true); // Block until lock is released

        try {
            $this->downloadIfNeeded($beforeDownload, $handleProgress);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadIfNeeded(?callable $beforeDownload, ?callable $handleProgress): void
    {
        if (! $this->dbUpdater->databaseFileExists()) {
            $this->downloadNewDb(false, $beforeDownload, $handleProgress);
            return;
        }

        $meta = $this->geoLiteDbReader->metadata();
        if ($this->buildIsTooOld($meta)) {
            $this->downloadNewDb(true, $beforeDownload, $handleProgress);
        }
    }

    private function buildIsTooOld(Metadata $meta): bool
    {
        $buildTimestamp = $this->resolveBuildTimestamp($meta);
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);
        $now = Chronos::now();

        return $now->gt($buildDate->addDays(35));
    }

    private function resolveBuildTimestamp(Metadata $meta): int
    {
        // In theory the buildEpoch should be an int, but it has been reported to come as a string.
        // See https://github.com/shlinkio/shlink/issues/1002 for context

        /** @var int|string $buildEpoch */
        $buildEpoch = $meta->buildEpoch;
        if (is_int($buildEpoch)) {
            return $buildEpoch;
        }

        $intBuildEpoch = (int) $buildEpoch;
        if ($buildEpoch === (string) $intBuildEpoch) {
            return $intBuildEpoch;
        }

        throw GeolocationDbUpdateFailedException::withInvalidEpochInOldDb($buildEpoch);
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadNewDb(bool $olderDbExists, ?callable $beforeDownload, ?callable $handleProgress): void
    {
        if ($beforeDownload !== null) {
            $beforeDownload($olderDbExists);
        }

        try {
            $this->dbUpdater->downloadFreshCopy($this->wrapHandleProgressCallback($handleProgress, $olderDbExists));
        } catch (RuntimeException $e) {
            throw $olderDbExists
                ? GeolocationDbUpdateFailedException::withOlderDb($e)
                : GeolocationDbUpdateFailedException::withoutOlderDb($e);
        }
    }

    private function wrapHandleProgressCallback(?callable $handleProgress, bool $olderDbExists): ?callable
    {
        if ($handleProgress === null) {
            return null;
        }

        return fn (int $total, int $downloaded) => $handleProgress($total, $downloaded, $olderDbExists);
    }
}
