<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Throwable;

use function Shlinkio\Shlink\Common\json_encode;

class NotifyNewShortUrlToRabbitMq
{
    private const NEW_SHORT_URL_QUEUE = 'https://shlink.io/new-short-url';

    public function __construct(
        private readonly AMQPStreamConnection $connection,
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

        if (! $this->connection->isConnected()) {
            $this->connection->reconnect();
        }

        $queue = self::NEW_SHORT_URL_QUEUE;
        $message = $this->shortUrlToMessage($shortUrl);

        try {
            $channel = $this->connection->channel();

            // Declare an exchange and a queue that will persist server restarts
            $exchange = $queue; // We use the same name for the exchange and the queue
            $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
            $channel->queue_declare($queue, false, true, false, false);

            // Bind the exchange and the queue together, and publish the message
            $channel->queue_bind($queue, $exchange);
            $channel->basic_publish($message, $exchange);

            $channel->close();
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify RabbitMQ with new short URL. {e}', ['e' => $e]);
        } finally {
            $this->connection->close();
        }
    }

    private function shortUrlToMessage(ShortUrl $shortUrl): AMQPMessage
    {
        $messageBody = json_encode($this->shortUrlTransformer->transform($shortUrl));
        return new AMQPMessage($messageBody, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }
}
