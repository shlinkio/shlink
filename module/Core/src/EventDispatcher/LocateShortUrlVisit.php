<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManager;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

use function sprintf;

class LocateShortUrlVisit
{
    private IpLocationResolverInterface $ipLocationResolver;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private GeolocationDbUpdaterInterface $dbUpdater;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        IpLocationResolverInterface $ipLocationResolver,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        GeolocationDbUpdaterInterface $dbUpdater,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->ipLocationResolver = $ipLocationResolver;
        $this->em = $em;
        $this->logger = $logger;
        $this->dbUpdater = $dbUpdater;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ShortUrlVisited $shortUrlVisited): void
    {
        // FIXME Temporarily handling DB connection reset here to fix https://github.com/shlinkio/shlink/issues/717
        //       Remove when https://github.com/shlinkio/shlink-event-dispatcher/issues/23 is implemented
        if ($this->em instanceof ReopeningEntityManager) {
            $this->em->open();
        }

        $visitId = $shortUrlVisited->visitId();

        try {
            /** @var Visit|null $visit */
            $visit = $this->em->find(Visit::class, $visitId);
            if ($visit === null) {
                $this->logger->warning('Tried to locate visit with id "{visitId}", but it does not exist.', [
                    'visitId' => $visitId,
                ]);
                return;
            }

            if ($this->downloadOrUpdateGeoLiteDb($visitId)) {
                $this->locateVisit($visitId, $shortUrlVisited->originalIpAddress(), $visit);
            }

            $this->eventDispatcher->dispatch(new VisitLocated($visitId));
        } finally {
            // FIXME Temporarily handling DB connection reset here to fix https://github.com/shlinkio/shlink/issues/717
            //       Remove when https://github.com/shlinkio/shlink-event-dispatcher/issues/23 is implemented
            $this->em->getConnection()->close();
            $this->em->clear();
        }
    }

    private function downloadOrUpdateGeoLiteDb(string $visitId): bool
    {
        try {
            $this->dbUpdater->checkDbUpdate(function (bool $olderDbExists): void {
                $this->logger->notice(sprintf('%s GeoLite2 database...', $olderDbExists ? 'Updating' : 'Downloading'));
            });
        } catch (GeolocationDbUpdateFailedException $e) {
            if (! $e->olderDbExists()) {
                $this->logger->error(
                    'GeoLite2 database download failed. It is not possible to locate visit with id {visitId}. {e}',
                    ['e' => $e, 'visitId' => $visitId],
                );
                return false;
            }

            $this->logger->warning('GeoLite2 database update failed. Proceeding with old version. {e}', ['e' => $e]);
        }

        return true;
    }

    private function locateVisit(string $visitId, ?string $originalIpAddress, Visit $visit): void
    {
        $isLocatable = $originalIpAddress !== null || $visit->isLocatable();
        $addr = $originalIpAddress ?? $visit->getRemoteAddr();

        try {
            $location = $isLocatable ? $this->ipLocationResolver->resolveIpLocation($addr) : Location::emptyInstance();

            $visit->locate(new VisitLocation($location));
            $this->em->flush();
        } catch (WrongIpException $e) {
            $this->logger->warning(
                'Tried to locate visit with id "{visitId}", but its address seems to be wrong. {e}',
                ['e' => $e, 'visitId' => $visitId],
            );
        }
    }
}
