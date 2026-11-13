<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\Config\Options\RealTimeUpdatesOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;

class NotifyVisitToRedis extends AbstractNotifyVisitListener
{
    public function __construct(
        PublishingHelperInterface $redisHelper,
        PublishingUpdatesGeneratorInterface $updatesGenerator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        RealTimeUpdatesOptions $realTimeUpdatesOptions,
        private readonly bool $enabled,
    ) {
        parent::__construct($redisHelper, $updatesGenerator, $em, $logger, $realTimeUpdatesOptions);
    }

    #[Override]
    protected function isEnabled(): bool
    {
        return $this->enabled;
    }

    #[Override]
    protected function getRemoteSystem(): RemoteSystem
    {
        return RemoteSystem::REDIS_PUB_SUB;
    }
}
