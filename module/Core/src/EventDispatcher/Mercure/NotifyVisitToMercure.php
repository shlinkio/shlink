<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;

class NotifyVisitToMercure extends AbstractNotifyVisitListener
{
    protected function isEnabled(): bool
    {
        return true;
    }

    protected function getRemoteSystem(): RemoteSystem
    {
        return RemoteSystem::MERCURE;
    }
}
