<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;

use function sprintf;

class LocateShortUrlVisit
{
    /** @var IpLocationResolverInterface */
    private $ipLocationResolver;
    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IpLocationResolverInterface $ipLocationResolver,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->ipLocationResolver = $ipLocationResolver;
        $this->em = $em;
        $this->logger = $logger;
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
