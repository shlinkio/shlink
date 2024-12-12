<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

use Cake\Chronos\Chronos;
use Closure;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\IpGeolocation\Exception\DbUpdateException;
use Shlinkio\Shlink\IpGeolocation\Exception\MissingLicenseException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\LockFactory;

use function is_int;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const string LOCK_NAME = 'geolocation-db-update';

    /** @var Closure(): Reader */
    private readonly Closure $geoLiteDbReaderFactory;

    /**
     * @param callable(): Reader $geoLiteDbReaderFactory
     */
    public function __construct(
        private readonly DbUpdaterInterface $dbUpdater,
        callable $geoLiteDbReaderFactory,
        private readonly LockFactory $locker,
        private readonly TrackingOptions $trackingOptions,
    ) {
        $this->geoLiteDbReaderFactory = $geoLiteDbReaderFactory(...);
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(
        GeolocationDownloadProgressHandlerInterface|null $downloadProgressHandler = null,
    ): GeolocationResult {
        if (! $this->trackingOptions->isGeolocationRelevant()) {
            return GeolocationResult::CHECK_SKIPPED;
        }

        $lock = $this->locker->createLock(self::LOCK_NAME);
        $lock->acquire(blocking: true);

        try {
            return $this->downloadIfNeeded($downloadProgressHandler);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadIfNeeded(
        GeolocationDownloadProgressHandlerInterface|null $downloadProgressHandler,
    ): GeolocationResult {
        if (! $this->dbUpdater->databaseFileExists()) {
            return $this->downloadNewDb($downloadProgressHandler, olderDbExists: false);
        }

        $meta = ($this->geoLiteDbReaderFactory)()->metadata();
        if ($this->buildIsTooOld($meta)) {
            return $this->downloadNewDb($downloadProgressHandler, olderDbExists: true);
        }

        return GeolocationResult::DB_IS_UP_TO_DATE;
    }

    private function buildIsTooOld(Metadata $meta): bool
    {
        $buildTimestamp = $this->resolveBuildTimestamp($meta);
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);

        return Chronos::now()->greaterThan($buildDate->addDays(35));
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
        GeolocationDownloadProgressHandlerInterface|null $downloadProgressHandler,
        bool $olderDbExists,
    ): GeolocationResult {
        $downloadProgressHandler?->beforeDownload($olderDbExists);

        try {
            $this->dbUpdater->downloadFreshCopy(
                static fn (int $total, int $downloaded)
                    => $downloadProgressHandler?->handleProgress($total, $downloaded, $olderDbExists),
            );
            return $olderDbExists ? GeolocationResult::DB_UPDATED : GeolocationResult::DB_CREATED;
        } catch (MissingLicenseException) {
            return GeolocationResult::LICENSE_MISSING;
        } catch (DbUpdateException $e) {
            throw $olderDbExists
                ? GeolocationDbUpdateFailedException::withOlderDb($e)
                : GeolocationDbUpdateFailedException::withoutOlderDb($e);
        }
    }
}
