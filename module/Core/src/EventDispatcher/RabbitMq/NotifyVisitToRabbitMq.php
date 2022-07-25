<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelperInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Throwable;

class NotifyVisitToRabbitMq
{
    public function __construct(
        private readonly RabbitMqPublishingHelperInterface $rabbitMqHelper,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $orphanVisitTransformer,
        private readonly DataTransformerInterface $shortUrlTransformer, // @phpstan-ignore-line
        private readonly bool $isEnabled,
    ) {
    }

    public function __invoke(VisitLocated $shortUrlLocated): void
    {
        if (! $this->isEnabled) {
            return;
        }

        $visitId = $shortUrlLocated->visitId;
        $visit = $this->em->find(Visit::class, $visitId);

        if ($visit === null) {
            $this->logger->warning('Tried to notify RabbitMQ for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $queues = $this->determineQueuesToPublishTo($visit);
        $payload = $this->visitToPayload($visit);

        try {
            foreach ($queues as $queue) {
                $this->rabbitMqHelper->publishPayloadInQueue($payload, $queue);
            }
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify RabbitMQ with new visit. {e}', ['e' => $e]);
        }
    }

    /**
     * @return string[]
     */
    private function determineQueuesToPublishTo(Visit $visit): array
    {
        if ($visit->isOrphan()) {
            return [Topic::NEW_ORPHAN_VISIT->value];
        }

        return [
            Topic::NEW_VISIT->value,
            Topic::newShortUrlVisit($visit->getShortUrl()?->getShortCode()),
        ];
    }

    private function visitToPayload(Visit $visit): array
    {
        // FIXME This was defined incorrectly.
        //       According to the spec, both the visit and the short URL it belongs to, should be published.
        //       The shape should be ['visit' => [...], 'shortUrl' => ?[...]]
        //       However, this would be a breaking change, so we need a flag that determines the shape of the payload.

        return ! $visit->isOrphan() ? $visit->jsonSerialize() : $this->orphanVisitTransformer->transform($visit);

        if ($visit->isOrphan()) { // @phpstan-ignore-line
            return ['visit' => $this->orphanVisitTransformer->transform($visit)];
        }

        return [
            'visit' => $visit->jsonSerialize(),
            'shortUrl' => $this->shortUrlTransformer->transform($visit->getShortUrl()),
        ];
    }
}
