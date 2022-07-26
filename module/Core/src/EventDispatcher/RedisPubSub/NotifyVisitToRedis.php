<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Throwable;

use function Functional\each;

class NotifyVisitToRedis
{
    public function __construct(
        private readonly PublishingHelperInterface $redisHelper,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $orphanVisitTransformer,
        private readonly DataTransformerInterface $shortUrlTransformer,
        private readonly bool $enabled,
    ) {
    }

    public function __invoke(VisitLocated $visitLocated): void
    {
        if (! $this->enabled) {
            return;
        }

        $visitId = $visitLocated->visitId;
        $visit = $this->em->find(Visit::class, $visitId);

        if ($visit === null) {
            $this->logger->warning(
                'Tried to notify Redis pub/sub for visit with id "{visitId}", but it does not exist.',
                ['visitId' => $visitId],
            );
            return;
        }

        $queues = $this->determineQueuesToPublishTo($visit);
        $payload = $this->visitToPayload($visit);

        try {
            each($queues, fn (string $queue) => $this->redisHelper->publishUpdate(
                Update::forTopicAndPayload($queue, $payload),
            ));
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify Redis pub/sub with new visit. {e}', ['e' => $e]);
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
        if ($visit->isOrphan()) {
            return ['visit' => $this->orphanVisitTransformer->transform($visit)];
        }

        return [
            'visit' => $visit->jsonSerialize(),
            'shortUrl' => $this->shortUrlTransformer->transform($visit->getShortUrl()),
        ];
    }
}
