<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\EventDispatcher\Listener;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\EventDispatcher\Listener\AsyncEventListener;
use Shlinkio\Shlink\EventDispatcher\Listener\EventListenerTask;
use stdClass;
use Swoole\Http\Server as HttpServer;

class AsyncEventListenerTest extends TestCase
{
    /** @var AsyncEventListener */
    private $eventListener;
    /** @var HttpServer */
    private $server;
    /** @var string */
    private $regularListenerName;

    public function setUp(): void
    {
        $this->regularListenerName = 'the_regular_listener';
        $this->server = $this->createMock(HttpServer::class);

        $this->eventListener = new AsyncEventListener($this->server, $this->regularListenerName);
    }

    /** @test */
    public function enqueuesTaskWhenInvoked(): void
    {
        $event = new stdClass();

        $this->server
            ->expects($this->once())
            ->method('task')
            ->with(new EventListenerTask($this->regularListenerName, $event));

        ($this->eventListener)($event);
    }
}
