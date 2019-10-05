<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

use function sprintf;

class LocateShortUrlVisit
{
    /** @var IpLocationResolverInterface */
    private $ipLocationResolver;
    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var GeolocationDbUpdaterInterface */
    private $dbUpdater;

    public function __construct(
        IpLocationResolverInterface $ipLocationResolver,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        GeolocationDbUpdaterInterface $dbUpdater
    ) {
        $this->ipLocationResolver = $ipLocationResolver;
        $this->em = $em;
        $this->logger = $logger;
        $this->dbUpdater = $dbUpdater;
    }

    public function __invoke(ShortUrlVisited $shortUrlVisited): void
    {
        $visitId = $shortUrlVisited->visitId();

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning(sprintf('Tried to locate visit with id "%s", but it does not exist.', $visitId));
            return;
        }

        try {
            $this->dbUpdater->checkDbUpdate(function (bool $olderDbExists) {
                $this->logger->notice(sprintf('%s GeoLite2 database...', $olderDbExists ? 'Updating' : 'Downloading'));
            });
        } catch (GeolocationDbUpdateFailedException $e) {
            if (! $e->olderDbExists()) {
                $this->logger->error(
                    sprintf(
                        'GeoLite2 database download failed. It is not possible to locate visit with id %s. {e}',
                        $visitId
                    ),
                    ['e' => $e]
                );
                return;
            }

            $this->logger->warning('GeoLite2 database update failed. Proceeding with old version. {e}', ['e' => $e]);
        }

        try {
            $location = $visit->isLocatable()
                ? $this->ipLocationResolver->resolveIpLocation($visit->getRemoteAddr())
                : Location::emptyInstance();
        } catch (WrongIpException $e) {
            $this->logger->warning(
                sprintf('Tried to locate visit with id "%s", but its address seems to be wrong. {e}', $visitId),
                ['e' => $e]
            );
            return;
        }

        $visit->locate(new VisitLocation($location));
        $this->em->flush($visit);
    }
}
