<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

class NotifyVisitToRabbitMq extends AbstractNotifyVisitListener
{
    public function __construct(
        PublishingHelperInterface $rabbitMqHelper,
        PublishingUpdatesGeneratorInterface $updatesGenerator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly DataTransformerInterface $orphanVisitTransformer,
        private readonly RabbitMqOptions $options,
    ) {
        parent::__construct($rabbitMqHelper, $updatesGenerator, $em, $logger);
    }

    /**
     * @return Update[]
     */
    protected function determineUpdatesForVisit(Visit $visit): array
    {
        // Once the two deprecated cases below have been removed, make parent method private
        if (! $this->options->legacyVisitsPublishing) {
            return parent::determineUpdatesForVisit($visit);
        }

        // This was defined incorrectly.
        // According to the spec, both the visit and the short URL it belongs to, should be published.
        // The shape should be ['visit' => [...], 'shortUrl' => ?[...]]
        // However, this would be a breaking change, so we need a flag that determines the shape of the payload.
        return $visit->isOrphan()
            ? [
                Update::forTopicAndPayload(
                    Topic::NEW_ORPHAN_VISIT->value,
                    $this->orphanVisitTransformer->transform($visit),
                ),
            ]
            : [
                Update::forTopicAndPayload(Topic::NEW_VISIT->value, $visit->jsonSerialize()),
                Update::forTopicAndPayload(
                    Topic::newShortUrlVisit($visit->getShortUrl()?->getShortCode()),
                    $visit->jsonSerialize(),
                ),
            ];
    }

    protected function isEnabled(): bool
    {
        return $this->options->enabled;
    }

    protected function getRemoteSystem(): RemoteSystem
    {
        return RemoteSystem::RABBIT_MQ;
    }
}
