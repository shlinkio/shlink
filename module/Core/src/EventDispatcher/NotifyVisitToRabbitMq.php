<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Throwable;

use function Shlinkio\Shlink\Common\json_encode;
use function sprintf;

class NotifyVisitToRabbitMq
{
    private const NEW_VISIT_QUEUE = 'https://shlink.io/new-visit';
    private const NEW_ORPHAN_VISIT_QUEUE = 'https://shlink.io/new-orphan-visit';

    public function __construct(
        private AMQPStreamConnection $connection,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private DataTransformerInterface $orphanVisitTransformer,
        private bool $isEnabled,
    ) {
    }

    public function __invoke(VisitLocated $shortUrlLocated): void
    {
        if (! $this->isEnabled) {
            return;
        }

        $visitId = $shortUrlLocated->visitId();
        $visit = $this->em->find(Visit::class, $visitId);

        if ($visit === null) {
            $this->logger->warning('Tried to notify RabbitMQ for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        if (! $this->connection->isConnected()) {
            $this->connection->reconnect();
        }

        $queues = $this->determineQueuesToPublishTo($visit);
        $message = $this->visitToMessage($visit);

        try {
            $channel = $this->connection->channel();

            foreach ($queues as $queue) {
                // Declare an exchange and a queue that will persist server restarts
                $exchange = $queue; // We use the same name for the exchange and the queue
                $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
                $channel->queue_declare($queue, false, true, false, false);

                // Bind the exchange and the queue together, and publish the message
                $channel->queue_bind($queue, $exchange);
                $channel->basic_publish($message, $exchange);
            }

            $channel->close();
        } catch (Throwable $e) {
            $this->logger->debug('Error while trying to notify RabbitMQ with new visit. {e}', ['e' => $e]);
        } finally {
            $this->connection->close();
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

    private function visitToMessage(Visit $visit): AMQPMessage
    {
        $messageBody = json_encode(! $visit->isOrphan() ? $visit : $this->orphanVisitTransformer->transform($visit));
        return new AMQPMessage($messageBody, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }
}
