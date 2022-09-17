<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyNewShortUrlListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;

class NotifyNewShortUrlToRabbitMq extends AbstractNotifyNewShortUrlListener
{
    public function __construct(
        PublishingHelperInterface $rabbitMqHelper,
        PublishingUpdatesGeneratorInterface $updatesGenerator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly RabbitMqOptions $options,
    ) {
        parent::__construct($rabbitMqHelper, $updatesGenerator, $em, $logger);
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
