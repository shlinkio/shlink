<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\RabbitMq;

use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;

class NotifyNewShortUrlToRabbitMq
{
    public function __invoke(ShortUrlCreated $shortUrlCreated)
    {
        // TODO: Implement __invoke() method.
    }
}
