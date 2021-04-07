<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Throwable;

use function sprintf;

class UpdateGeoLiteDb
{
    private GeolocationDbUpdaterInterface $dbUpdater;
    private LoggerInterface $logger;

    public function __construct(GeolocationDbUpdaterInterface $dbUpdater, LoggerInterface $logger)
    {
        $this->dbUpdater = $dbUpdater;
        $this->logger = $logger;
    }

    public function __invoke(): void
    {
        $beforeDownload = fn (bool $olderDbExists) => $this->logger->notice(
            sprintf('%s GeoLite2 db file...', $olderDbExists ? 'Updating' : 'Downloading'),
        );
        $handleProgress = function (int $total, int $downloaded, bool $olderDbExists): void {
            if ($total > $downloaded) {
                return;
            }

            $this->logger->notice(sprintf('Finished %s GeoLite2 db file', $olderDbExists ? 'updating' : 'downloading'));
        };

        try {
            $this->dbUpdater->checkDbUpdate($beforeDownload, $handleProgress);
        } catch (Throwable $e) {
            $this->logger->error('GeoLite2 database download failed. {e}', ['e' => $e]);
        }
    }
}
