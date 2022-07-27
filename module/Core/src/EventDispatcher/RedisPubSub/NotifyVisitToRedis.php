<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;

class NotifyVisitToRedis extends AbstractNotifyVisitListener
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

    protected function getRemoteSystemName(): string
    {
        return 'Redis pub/sub';
    }
}
