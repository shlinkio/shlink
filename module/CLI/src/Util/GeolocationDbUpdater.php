<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\IpGeolocation\Exception\RuntimeException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\LockFactory;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const LOCK_NAME = 'geolocation-db-update';

    private DbUpdaterInterface $dbUpdater;
    private Reader $geoLiteDbReader;
    private LockFactory $locker;

    public function __construct(DbUpdaterInterface $dbUpdater, Reader $geoLiteDbReader, LockFactory $locker)
    {
        $this->dbUpdater = $dbUpdater;
        $this->geoLiteDbReader = $geoLiteDbReader;
        $this->locker = $locker;
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(?callable $mustBeUpdated = null, ?callable $handleProgress = null): void
    {
        $lock = $this->locker->createLock(self::LOCK_NAME);
        $lock->acquire(true); // Block until lock is released

        try {
            $this->downloadIfNeeded($mustBeUpdated, $handleProgress);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadIfNeeded(?callable $mustBeUpdated, ?callable $handleProgress): void
    {
        if (! $this->dbUpdater->databaseFileExists()) {
            $this->downloadNewDb(false, $mustBeUpdated, $handleProgress);
            return;
        }

        $meta = $this->geoLiteDbReader->metadata();
        if ($this->buildIsTooOld($meta->buildEpoch)) {
            $this->downloadNewDb(true, $mustBeUpdated, $handleProgress);
        }
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadNewDb(bool $olderDbExists, ?callable $mustBeUpdated, ?callable $handleProgress): void
    {
        if ($mustBeUpdated !== null) {
            $mustBeUpdated($olderDbExists);
        }

        try {
            $this->dbUpdater->downloadFreshCopy($handleProgress);
        } catch (RuntimeException $e) {
            throw GeolocationDbUpdateFailedException::create($olderDbExists, $e);
        }
    }

    private function buildIsTooOld(int $buildTimestamp): bool
    {
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);
        $now = Chronos::now();
        return $now->gt($buildDate->addDays(35));
    }
}
