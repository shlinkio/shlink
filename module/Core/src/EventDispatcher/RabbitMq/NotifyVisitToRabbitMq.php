<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelperInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Throwable;

use function sprintf;

class NotifyVisitToRabbitMq
{
    private const NEW_VISIT_QUEUE = 'https://shlink.io/new-visit';
    private const NEW_ORPHAN_VISIT_QUEUE = 'https://shlink.io/new-orphan-visit';

    public function __construct(
        private readonly RabbitMqPublishingHelperInterface $rabbitMqHelper,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $orphanVisitTransformer,
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
            return [self::NEW_ORPHAN_VISIT_QUEUE];
        }

        return [
            self::NEW_VISIT_QUEUE,
            sprintf('%s/%s', self::NEW_VISIT_QUEUE, $visit->getShortUrl()?->getShortCode()),
        ];
    }

    private function visitToPayload(Visit $visit): array
    {
        return ! $visit->isOrphan() ? $visit->jsonSerialize() : $this->orphanVisitTransformer->transform($visit);
    }
}
