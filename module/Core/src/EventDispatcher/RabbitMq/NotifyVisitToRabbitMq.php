<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Throwable;

use function Functional\each;

class NotifyVisitToRabbitMq
{
    public function __construct(
        private readonly PublishingHelperInterface $rabbitMqHelper,
        private readonly PublishingUpdatesGeneratorInterface $updatesGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $orphanVisitTransformer,
        private readonly RabbitMqOptions $options,
    ) {
    }

    public function __invoke(VisitLocated $visitLocated): void
    {
        if (! $this->options->isEnabled()) {
            return;
        }

        $visitId = $visitLocated->visitId;
        $visit = $this->em->find(Visit::class, $visitId);

        if ($visit === null) {
            $this->logger->warning('Tried to notify RabbitMQ for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $updates = $this->determineUpdatesForVisit($visit);

        try {
            each($updates, fn (Update $update) => $this->rabbitMqHelper->publishUpdate($update));
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify RabbitMQ with new visit. {e}', ['e' => $e]);
        }
    }

    /**
     * @return Update[]
     */
    private function determineUpdatesForVisit(Visit $visit): array
    {
        return match (true) {
            // This was defined incorrectly.
            // According to the spec, both the visit and the short URL it belongs to, should be published.
            // The shape should be ['visit' => [...], 'shortUrl' => ?[...]]
            // However, this would be a breaking change, so we need a flag that determines the shape of the payload.
            $this->options->legacyVisitsPublishing() && $visit->isOrphan() => [
                Update::forTopicAndPayload(
                    Topic::NEW_ORPHAN_VISIT->value,
                    $this->orphanVisitTransformer->transform($visit),
                ),
            ],
            $this->options->legacyVisitsPublishing() && ! $visit->isOrphan() => [
                Update::forTopicAndPayload(Topic::NEW_VISIT->value, $visit->jsonSerialize()),
                Update::forTopicAndPayload(
                    Topic::newShortUrlVisit($visit->getShortUrl()?->getShortCode()),
                    $visit->jsonSerialize(),
                ),
            ],

            // Once the two deprecated cases above have been remove, replace this with a simple "if" and early return.
            $visit->isOrphan() => [$this->updatesGenerator->newOrphanVisitUpdate($visit)],
            default => [
                $this->updatesGenerator->newShortUrlVisitUpdate($visit),
                $this->updatesGenerator->newVisitUpdate($visit),
            ],
        };
    }
}
