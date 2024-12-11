<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDownloadProgressHandlerInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationResult;
use Throwable;

use function sprintf;

readonly class UpdateGeoLiteDb
{
    public function __construct(
        private GeolocationDbUpdaterInterface $dbUpdater,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(): void
    {
        try {
            $result = $this->dbUpdater->checkDbUpdate(
                new class ($this->logger) implements GeolocationDownloadProgressHandlerInterface {
                    public function __construct(
                        private readonly LoggerInterface $logger,
                        private bool $messageLogged = false,
                    ) {
                    }

                    public function beforeDownload(bool $olderDbExists): void
                    {
                        $this->logger->notice(
                            sprintf('%s GeoLite2 db file...', $olderDbExists ? 'Updating' : 'Downloading'),
                        );
                    }

                    public function handleProgress(int $total, int $downloaded, bool $olderDbExists): void
                    {
                        if ($this->messageLogged || $total > $downloaded) {
                            return;
                        }

                        $this->messageLogged = true;
                        $this->logger->notice(
                            sprintf('Finished %s GeoLite2 db file', $olderDbExists ? 'updating' : 'downloading'),
                        );
                    }
                },
            );
            if ($result === GeolocationResult::DB_CREATED) {
                $this->eventDispatcher->dispatch(new GeoLiteDbCreated());
            }
        } catch (Throwable $e) {
            $this->logger->error('GeoLite2 database download failed. {e}', ['e' => $e]);
        }
    }
}
