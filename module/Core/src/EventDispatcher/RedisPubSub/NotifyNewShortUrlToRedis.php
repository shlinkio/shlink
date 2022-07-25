<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub;

use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;

class NotifyNewShortUrlToRedis
{
    public function __invoke(ShortUrlCreated $shortUrlCreated): void
    {
        // TODO: Implement __invoke() method.
    }
}
