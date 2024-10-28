<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Throwable;

readonly class LocateVisit
{
    public function __construct(
        private IpLocationResolverInterface $ipLocationResolver,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private DbUpdaterInterface $dbUpdater,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UrlVisited $shortUrlVisited): void
    {
        $visitId = $shortUrlVisited->visitId;

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning('Tried to locate visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $this->locateVisit($visitId, $shortUrlVisited->originalIpAddress, $visit);
        $this->eventDispatcher->dispatch(new VisitLocated($visitId, $shortUrlVisited->originalIpAddress));
    }

    private function locateVisit(string $visitId, string|null $originalIpAddress, Visit $visit): void
    {
        if (! $this->dbUpdater->databaseFileExists()) {
            $this->logger->warning('Tried to locate visit with id "{visitId}", but a GeoLite2 db was not found.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $isLocatable = $originalIpAddress !== null || $visit->isLocatable();
        $addr = $originalIpAddress ?? $visit->remoteAddr ?? '';

        try {
            $location = $isLocatable ? $this->ipLocationResolver->resolveIpLocation($addr) : Location::emptyInstance();

            $visit->locate(VisitLocation::fromGeolocation($location));
            $this->em->flush();
        } catch (WrongIpException $e) {
            $this->logger->warning(
                'Tried to locate visit with id "{visitId}", but its address seems to be wrong. {e}',
                ['e' => $e, 'visitId' => $visitId],
            );
        } catch (Throwable $e) {
            $this->logger->error(
                'An unexpected error occurred while trying to locate visit with id "{visitId}". {e}',
                ['e' => $e, 'visitId' => $visitId],
            );
        }
    }
}
