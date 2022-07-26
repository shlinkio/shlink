<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Cache\RedisPublishingHelperInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Throwable;

class NotifyNewShortUrlToRedis
{
    public function __construct(
        private readonly RedisPublishingHelperInterface $redisHelper,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $shortUrlTransformer,
        private readonly bool $enabled,
    ) {
    }

    public function __invoke(ShortUrlCreated $shortUrlCreated): void
    {
        if (! $this->enabled) {
            return;
        }

        $shortUrlId = $shortUrlCreated->shortUrlId;
        $shortUrl = $this->em->find(ShortUrl::class, $shortUrlId);

        if ($shortUrl === null) {
            $this->logger->warning(
                'Tried to notify Redis pub/sub for new short URL with id "{shortUrlId}", but it does not exist.',
                ['shortUrlId' => $shortUrlId],
            );
            return;
        }

        try {
            $this->redisHelper->publishPayloadInQueue(
                ['shortUrl' => $this->shortUrlTransformer->transform($shortUrl)],
                Topic::NEW_SHORT_URL->value,
            );
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify Redis pub/sub with new short URL. {e}', ['e' => $e]);
        }
    }
}
