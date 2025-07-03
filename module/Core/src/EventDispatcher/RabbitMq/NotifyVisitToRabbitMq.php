<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\Config\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\Config\Options\RealTimeUpdatesOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;

class NotifyVisitToRabbitMq extends AbstractNotifyVisitListener
{
    public function __construct(
        PublishingHelperInterface $rabbitMqHelper,
        PublishingUpdatesGeneratorInterface $updatesGenerator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        RealTimeUpdatesOptions $realTimeUpdatesOptions,
        private readonly RabbitMqOptions $options,
    ) {
        parent::__construct($rabbitMqHelper, $updatesGenerator, $em, $logger, $realTimeUpdatesOptions);
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
