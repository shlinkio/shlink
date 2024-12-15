<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Geolocation\Entity\GeolocationDbUpdate;
use Shlinkio\Shlink\IpGeolocation\Exception\DbUpdateException;
use Shlinkio\Shlink\IpGeolocation\Exception\MissingLicenseException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock\LockFactory;

use function count;
use function Shlinkio\Shlink\Core\ArrayUtils\every;
use function sprintf;

readonly class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const string LOCK_NAME = 'geolocation-db-update';

    public function __construct(
        private DbUpdaterInterface $dbUpdater,
        private LockFactory $locker,
        private TrackingOptions $trackingOptions,
        private EntityManagerInterface $em,
    ) {
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
        $maxRecentAttemptsToCheck = 15; // TODO Make this configurable

        // Get last 15 download attempts
        $recentDownloads = $this->em->getRepository(GeolocationDbUpdate::class)->findBy(
            criteria: ['filesystemId' => GeolocationDbUpdate::currentFilesystemId()],
            orderBy: ['dateUpdated' => 'DESC'],
            limit: $maxRecentAttemptsToCheck,
        );
        $mostRecentDownload = $recentDownloads[0] ?? null;
        $amountOfRecentAttempts = count($recentDownloads);

        // If most recent attempt is in progress, skip check.
        // This is a safety check in case the lock is released before the previous download has finished.
        if ($mostRecentDownload?->isInProgress()) {
            return GeolocationResult::CHECK_SKIPPED;
        }

        // If all recent attempts are errors, and the most recent one is not old enough, skip download
        if (
            $amountOfRecentAttempts === $maxRecentAttemptsToCheck
            && every($recentDownloads, static fn (GeolocationDbUpdate $update) => $update->isError())
            && ! $mostRecentDownload->needsUpdate()
        ) {
            return GeolocationResult::CHECK_SKIPPED;
        }

        // Try to download if there are no attempts, the database file does not exist or most recent attempt was
        // successful and is old enough
        $olderDbExists = $amountOfRecentAttempts > 0 && $this->dbUpdater->databaseFileExists();
        if (! $olderDbExists || $mostRecentDownload->needsUpdate()) {
            return $this->downloadAndTrackUpdate($downloadProgressHandler, $olderDbExists);
        }

        return GeolocationResult::DB_IS_UP_TO_DATE;
    }

    /**
     * @throws GeolocationDbUpdateFailedException
     */
    private function downloadAndTrackUpdate(
        GeolocationDownloadProgressHandlerInterface|null $downloadProgressHandler,
        bool $olderDbExists,
    ): GeolocationResult {
        $dbUpdate = GeolocationDbUpdate::forFilesystemId();
        $this->em->persist($dbUpdate);
        $this->em->flush();

        try {
            $result = $this->downloadNewDb($downloadProgressHandler, $olderDbExists);
            $dbUpdate->finishSuccessfully();
            return $result;
        } catch (MissingLicenseException) {
            $dbUpdate->finishWithError('Geolocation license key is missing');
            return GeolocationResult::LICENSE_MISSING;
        } catch (GeolocationDbUpdateFailedException $e) {
            $dbUpdate->finishWithError(
                sprintf('%s. Prev: %s', $e->getMessage(), $e->getPrevious()?->getMessage() ?? '-'),
            );
            throw $e;
        } finally {
            $this->em->flush();
        }
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
        } catch (DbUpdateException $e) {
            throw $olderDbExists
                ? GeolocationDbUpdateFailedException::withOlderDb($e)
                : GeolocationDbUpdateFailedException::withoutOlderDb($e);
        }
    }
}
