<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use InvalidArgumentException;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;

class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    /** @var DbUpdaterInterface */
    private $dbUpdater;
    /** @var Reader */
    private $geoLiteDbReader;

    public function __construct(DbUpdaterInterface $dbUpdater, Reader $geoLiteDbReader)
    {
        $this->dbUpdater = $dbUpdater;
        $this->geoLiteDbReader = $geoLiteDbReader;
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(callable $mustBeUpdated = null, callable $handleProgress = null): void
    {
        try {
            $meta = $this->geoLiteDbReader->metadata();
            if ($this->buildIsOlderThanOneWeek($meta->__get('buildEpoch'))) {
                $this->downloadNewDb(true, $mustBeUpdated, $handleProgress);
            }
        } catch (InvalidArgumentException $e) {
            // This is the exception thrown by the reader when the database file does not exist
            $this->downloadNewDb(false, $mustBeUpdated, $handleProgress);
        }
    }

    private function buildIsOlderThanOneWeek(int $buildTimestamp): bool
    {
        $buildDate = Chronos::createFromTimestamp($buildTimestamp);
        $now = Chronos::now();
        return $now->gt($buildDate->addDays(7));
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
            $mustBeUpdated();
        }

        try {
            $this->dbUpdater->downloadFreshCopy($handleProgress);
        } catch (RuntimeException $e) {
            throw GeolocationDbUpdateFailedException::create($olderDbExists, $e);
        }
    }
}
