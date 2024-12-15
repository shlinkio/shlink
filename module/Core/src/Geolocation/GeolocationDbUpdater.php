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
use Throwable;

use function sprintf;

readonly class GeolocationDbUpdater implements GeolocationDbUpdaterInterface
{
    private const string LOCK_NAME = 'geolocation-db-update';

    public function __construct(
        private DbUpdaterInterface $dbUpdater,
        private LockFactory $locker,
        private TrackingOptions $trackingOptions,
        private EntityManagerInterface $em,
        private int $maxRecentAttemptsToCheck = 15, // TODO Make this configurable
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
        $recentDownloads = $this->em->getRepository(GeolocationDbUpdate::class)->findBy(
            criteria: ['filesystemId' => GeolocationDbUpdate::currentFilesystemId()],
            orderBy: ['dateUpdated' => 'DESC'],
            limit: $this->maxRecentAttemptsToCheck,
        );
        $mostRecentDownload = $recentDownloads[0] ?? null;

        // If most recent attempt is in progress, skip check.
        // This is a safety check in case the lock is released before the previous download has finished.
        if ($mostRecentDownload?->isInProgress()) {
            return GeolocationResult::UPDATE_IN_PROGRESS;
        }

        $amountOfErrorsSinceLastSuccess = 0;
        foreach ($recentDownloads as $recentDownload) {
            // Count attempts until a success is found
            if ($recentDownload->isSuccess()) {
                break;
            }
            $amountOfErrorsSinceLastSuccess++;
        }

        // If max amount of consecutive errors has been reached and the most recent one is not old enough, skip download
        // for 2 days to avoid hitting potential API limits in geolocation services
        $lastAttemptIsError = $mostRecentDownload !== null && $mostRecentDownload->isError();
        // FIXME Once max errors are reached there will be one attempt every 2 days, but it should be 15 attempts every
        //       2 days. Leaving like this for simplicity for now.
        $maxConsecutiveErrorsReached = $amountOfErrorsSinceLastSuccess === $this->maxRecentAttemptsToCheck;
        if ($lastAttemptIsError && $maxConsecutiveErrorsReached && ! $mostRecentDownload->isOlderThan(days: 2)) {
            return GeolocationResult::MAX_ERRORS_REACHED;
        }

        // Try to download if:
        // - There are no attempts or the database file does not exist
        // - Last update errored (and implicitly, the max amount of consecutive errors has not been reached)
        // - Most recent attempt is older than 30 days (and implicitly, successful)
        $olderDbExists = $mostRecentDownload !== null && $this->dbUpdater->databaseFileExists();
        if (! $olderDbExists || $lastAttemptIsError || $mostRecentDownload->isOlderThan(days: 30)) {
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
                sprintf('%s Prev: %s', $e->getMessage(), $e->getPrevious()?->getMessage() ?? '-'),
            );
            throw $e;
        } catch (Throwable $e) {
            $dbUpdate->finishWithError(sprintf('Unknown error: %s', $e->getMessage()));
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
