<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyNewShortUrlListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;

class NotifyNewShortUrlToRedis extends AbstractNotifyNewShortUrlListener
{
    public function __construct(
        PublishingHelperInterface $redisHelper,
        PublishingUpdatesGeneratorInterface $updatesGenerator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly bool $enabled,
    ) {
        parent::__construct($redisHelper, $updatesGenerator, $em, $logger);
    }

    protected function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function getRemoteSystem(): RemoteSystem
    {
        return RemoteSystem::REDIS_PUB_SUB;
    }
}
