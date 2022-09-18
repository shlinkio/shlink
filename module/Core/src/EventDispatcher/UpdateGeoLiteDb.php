<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationResult;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Throwable;

use function sprintf;

class UpdateGeoLiteDb
{
    public function __construct(
        private readonly GeolocationDbUpdaterInterface $dbUpdater,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(): void
    {
        $beforeDownload = fn (bool $olderDbExists) => $this->logger->notice(
            sprintf('%s GeoLite2 db file...', $olderDbExists ? 'Updating' : 'Downloading'),
        );
        $messageLogged = false;
        $handleProgress = function (int $total, int $downloaded, bool $olderDbExists) use (&$messageLogged): void {
            if ($messageLogged || $total > $downloaded) {
                return;
            }

            $messageLogged = true;
            $this->logger->notice(sprintf('Finished %s GeoLite2 db file', $olderDbExists ? 'updating' : 'downloading'));
        };

        try {
            $result = $this->dbUpdater->checkDbUpdate($beforeDownload, $handleProgress);
            if ($result === GeolocationResult::DB_CREATED) {
                $this->eventDispatcher->dispatch(new GeoLiteDbCreated());
            }
        } catch (Throwable $e) {
            $this->logger->error('GeoLite2 database download failed. {e}', ['e' => $e]);
        }
    }
}
