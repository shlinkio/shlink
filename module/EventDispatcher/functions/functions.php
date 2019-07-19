<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher;

use Swoole\Http\Server as HttpServer;

function asyncListener(HttpServer $server, string $regularListenerName): Listener\AsyncEventListener
{
    return new Listener\AsyncEventListener($server, $regularListenerName);
}
