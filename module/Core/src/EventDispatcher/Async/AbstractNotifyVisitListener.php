<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Async;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Throwable;

use function Functional\each;

abstract class AbstractNotifyVisitListener extends AbstractAsyncListener
{
    public function __construct(
        private readonly PublishingHelperInterface $publishingHelper,
        private readonly PublishingUpdatesGeneratorInterface $updatesGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(VisitLocated $visitLocated): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $visitId = $visitLocated->visitId;
        $visit = $this->em->find(Visit::class, $visitId);
        $name = $this->getRemoteSystem()->value;

        if ($visit === null) {
            $this->logger->warning(
                'Tried to notify {name} for visit with id "{visitId}", but it does not exist.',
                ['visitId' => $visitId, 'name' => $name],
            );
            return;
        }

        $updates = $this->determineUpdatesForVisit($visit);

        try {
            each($updates, fn (Update $update) => $this->publishingHelper->publishUpdate($update));
        } catch (Throwable $e) {
            $this->logger->debug(
                'Error while trying to notify {name} with new visit. {e}',
                ['e' => $e, 'name' => $name],
            );
        }
    }

    /**
     * @return Update[]
     */
    protected function determineUpdatesForVisit(Visit $visit): array
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
