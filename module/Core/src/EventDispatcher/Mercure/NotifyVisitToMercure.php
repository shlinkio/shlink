<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Mercure;

use Override;
use Shlinkio\Shlink\Core\EventDispatcher\Async\AbstractNotifyVisitListener;
use Shlinkio\Shlink\Core\EventDispatcher\Async\RemoteSystem;

class NotifyVisitToMercure extends AbstractNotifyVisitListener
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
