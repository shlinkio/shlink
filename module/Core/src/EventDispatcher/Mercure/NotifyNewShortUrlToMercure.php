<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyNewShortUrlListener;

class NotifyNewShortUrlToMercure extends AbstractNotifyNewShortUrlListener
{
    protected function isEnabled(): bool
    {
        return true;
    }

    protected function getRemoteSystemName(): string
    {
        return 'Mercure';
    }
}
