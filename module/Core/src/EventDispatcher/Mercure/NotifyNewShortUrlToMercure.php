<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Override;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyNewShortUrlListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;

class NotifyNewShortUrlToMercure extends AbstractNotifyNewShortUrlListener
{
    #[Override]
    protected function isEnabled(): bool
    {
        return true;
    }

    #[Override]
    protected function getRemoteSystem(): RemoteSystem
    {
        return RemoteSystem::MERCURE;
    }
}
