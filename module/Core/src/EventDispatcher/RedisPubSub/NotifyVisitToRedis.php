<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;

class NotifyVisitToRedis
{
    public function __invoke(VisitLocated $visitLocated): void
    {
        // TODO: Implement __invoke() method.
    }
}
