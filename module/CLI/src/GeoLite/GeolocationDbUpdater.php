<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\GeoLite;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\IpGeolocation\Exception\DbUpdateException;
use Shlinkio\Shlink\IpGeolocation\Exception\MissingLicenseException;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\LockFactory;

use function is_int;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const LOCK_NAME = 'geolocation-db-update';

    public function __construct(
        private readonly DbUpdaterInterface $dbUpdater,
        private readonly Reader $geoLiteDbReader,
        private readonly LockFactory $locker,
        private readonly TrackingOptions $trackingOptions,
    ) {
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(?callable $beforeDownload = null, ?callable $handleProgress = null): GeolocationResult
    {
        if ($this->trackingOptions->disableTracking || $this->trackingOptions->disableIpTracking) {
            return GeolocationResult::CHECK_SKIPPED;
        }

        $lock = $this->locker->createLock(self::LOCK_NAME);
        $lock->acquire(true); // Block until lock is released

        try {
            return $this->downloadIfNeeded($beforeDownload, $handleProgress);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadIfNeeded(?callable $beforeDownload, ?callable $handleProgress): GeolocationResult
    {
        if (! $this->dbUpdater->databaseFileExists()) {
            return $this->downloadNewDb(false, $beforeDownload, $handleProgress);
        }

        $meta = $this->geoLiteDbReader->metadata();
        if ($this->buildIsTooOld($meta)) {
            return $this->downloadNewDb(true, $beforeDownload, $handleProgress);
        }

        return GeolocationResult::DB_IS_UP_TO_DATE;
    }

    private function buildIsTooOld(Metadata $meta): bool
    {
        $buildTimestamp = $this->resolveBuildTimestamp($meta);
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);

        return Chronos::now()->gt($buildDate->addDays(35));
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
    private function downloadNewDb(
        bool $olderDbExists,
        ?callable $beforeDownload,
        ?callable $handleProgress,
    ): GeolocationResult {
        if ($beforeDownload !== null) {
            $beforeDownload($olderDbExists);
        }

        try {
            $this->dbUpdater->downloadFreshCopy($this->wrapHandleProgressCallback($handleProgress, $olderDbExists));
            return $olderDbExists ? GeolocationResult::DB_UPDATED : GeolocationResult::DB_CREATED;
        } catch (MissingLicenseException) {
            // If there's no license key, just ignore the error
            return GeolocationResult::CHECK_SKIPPED;
        } catch (DbUpdateException | WrongIpException $e) {
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

        return static fn (int $total, int $downloaded) => $handleProgress($total, $downloaded, $olderDbExists);
    }
}
