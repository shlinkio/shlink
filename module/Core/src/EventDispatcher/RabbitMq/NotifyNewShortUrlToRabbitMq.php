<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelperInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Throwable;

class NotifyNewShortUrlToRabbitMq
{
    private const NEW_SHORT_URL_QUEUE = 'https://shlink.io/new-short-url';

    public function __construct(
        private readonly RabbitMqPublishingHelperInterface $rabbitMqHelper,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly DataTransformerInterface $shortUrlTransformer,
        private readonly bool $isEnabled,
    ) {
    }

    public function __invoke(ShortUrlCreated $shortUrlCreated): void
    {
        if (! $this->isEnabled) {
            return;
        }

        $shortUrlId = $shortUrlCreated->shortUrlId;
        $shortUrl = $this->em->find(ShortUrl::class, $shortUrlId);

        if ($shortUrl === null) {
            $this->logger->warning(
                'Tried to notify RabbitMQ for new short URL with id "{shortUrlId}", but it does not exist.',
                ['shortUrlId' => $shortUrlId],
            );
            return;
        }

        try {
            $this->rabbitMqHelper->publishPayloadInQueue(
                $this->shortUrlTransformer->transform($shortUrl),
                self::NEW_SHORT_URL_QUEUE,
            );
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify RabbitMQ with new short URL. {e}', ['e' => $e]);
        }
    }
}
