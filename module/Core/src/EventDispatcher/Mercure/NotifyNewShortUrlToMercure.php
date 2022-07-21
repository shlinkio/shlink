<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;

class NotifyNewShortUrlToMercure
{
    public function __invoke(ShortUrlCreated $shortUrlCreated)
    {
        // TODO: Implement __invoke() method.
    }
}
