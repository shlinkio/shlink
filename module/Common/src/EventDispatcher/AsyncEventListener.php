<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Swoole\Http\Server as HttpServer;

class AsyncEventListener
{
    /** @var string */
    private $regularListenerName;
    /** @var HttpServer */
    private $server;

    public function __construct(HttpServer $server, string $regularListenerName)
    {
        $this->regularListenerName = $regularListenerName;
        $this->server = $server;
    }

    public function __invoke(object $event): void
    {
        $this->server->task(new Task($this->regularListenerName, $event));
    }
}
