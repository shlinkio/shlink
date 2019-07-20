<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use InvalidArgumentException;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\Factory as Locker;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const LOCK_NAME = 'geolocation-db-update';

    /** @var DbUpdaterInterface */
    private $dbUpdater;
    /** @var Reader */
    private $geoLiteDbReader;
    /** @var Locker */
    private $locker;

    public function __construct(DbUpdaterInterface $dbUpdater, Reader $geoLiteDbReader, Locker $locker)
    {
        $this->dbUpdater = $dbUpdater;
        $this->geoLiteDbReader = $geoLiteDbReader;
        $this->locker = $locker;
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(callable $mustBeUpdated = null, callable $handleProgress = null): void
    {
        $lock = $this->locker->createLock(self::LOCK_NAME);
        $lock->acquire(true); // Block until lock is released

        try {
            $meta = $this->geoLiteDbReader->metadata();
            if ($this->buildIsTooOld($meta->__get('buildEpoch'))) {
                $this->downloadNewDb(true, $mustBeUpdated, $handleProgress);
            }
        } catch (InvalidArgumentException $e) {
            // This is the exception thrown by the reader when the database file does not exist
            $this->downloadNewDb(false, $mustBeUpdated, $handleProgress);
        } finally {
            $lock->release();
        }
    }

    private function buildIsTooOld(int $buildTimestamp): bool
    {
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);
        $now = Chronos::now();
        return $now->gt($buildDate->addDays(35));
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadNewDb(
        bool $olderDbExists,
        callable $mustBeUpdated = null,
        callable $handleProgress = null
    ): void {
        if ($mustBeUpdated !== null) {
            $mustBeUpdated($olderDbExists);
        }

        try {
            $this->dbUpdater->downloadFreshCopy($handleProgress);
        } catch (RuntimeException $e) {
            throw GeolocationDbUpdateFailedException::create($olderDbExists, $e);
        }
    }
}
