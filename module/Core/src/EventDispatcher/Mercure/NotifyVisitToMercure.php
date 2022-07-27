<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Throwable;

use function Functional\each;

class NotifyVisitToMercure
{
    public function __construct(
        private readonly PublishingHelperInterface $mercureHelper,
        private readonly PublishingUpdatesGeneratorInterface $updatesGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(VisitLocated $visitLocated): void
    {
        $visitId = $visitLocated->visitId;

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning('Tried to notify mercure for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        try {
            each(
                $this->determineUpdatesForVisit($visit),
                fn (Update $update) => $this->mercureHelper->publishUpdate($update),
            );
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify mercure hub with new visit. {e}', [
                'e' => $e,
            ]);
        }
    }

    /**
     * @return Update[]
     */
    private function determineUpdatesForVisit(Visit $visit): array
    {
        if ($visit->isOrphan()) {
            return [$this->updatesGenerator->newOrphanVisitUpdate($visit)];
        }

        return [
            $this->updatesGenerator->newShortUrlVisitUpdate($visit),
            $this->updatesGenerator->newVisitUpdate($visit),
        ];
    }
}
