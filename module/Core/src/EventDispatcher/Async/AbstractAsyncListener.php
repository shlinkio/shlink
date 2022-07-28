<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Async;

abstract class AbstractAsyncListener
{
    abstract protected function isEnabled(): bool;

    abstract protected function getRemoteSystem(): RemoteSystem;
}
