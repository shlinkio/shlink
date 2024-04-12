<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Matomo;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSenderInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Throwable;

readonly class SendVisitToMatomo
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private MatomoOptions $matomoOptions,
        private MatomoVisitSenderInterface $visitSender,
    ) {
    }

    public function __invoke(VisitLocated $visitLocated): void
    {
        if (! $this->matomoOptions->enabled) {
            return;
        }

        $visitId = $visitLocated->visitId;

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning('Tried to send visit with id "{visitId}" to matomo, but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        try {
            $this->visitSender->sendVisitToMatomo($visit, $visitLocated->originalIpAddress);
        } catch (Throwable $e) {
            // Capture all exceptions to make sure this does not interfere with the regular execution
            $this->logger->error('An error occurred while trying to send visit to Matomo. {e}', ['e' => $e]);
        }
    }
}
