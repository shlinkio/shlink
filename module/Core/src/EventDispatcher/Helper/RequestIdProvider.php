<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Helper;

use Shlinkio\Shlink\Common\Middleware\RequestIdMiddleware;
use Shlinkio\Shlink\EventDispatcher\Util\RequestIdProviderInterface;

readonly class RequestIdProvider implements RequestIdProviderInterface
{
    public function __construct(private RequestIdMiddleware $requestIdMiddleware)
    {
    }

    public function currentRequestId(): string
    {
        return $this->requestIdMiddleware->currentRequestId();
    }
}
