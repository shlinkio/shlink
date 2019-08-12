<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\EventDispatcher\Async;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\EventDispatcher\Async\TaskRunner;
use Shlinkio\Shlink\EventDispatcher\Async\TaskRunnerDelegator;
use Swoole\Http\Server as HttpServer;

class TaskRunnerDelegatorTest extends TestCase
{
    /** @var TaskRunnerDelegator */
    private $delegator;

    public function setUp(): void
    {
        $this->delegator = new TaskRunnerDelegator();
    }

    /** @test */
    public function serverIsFetchedFromCallbackAndDecorated(): void
    {
        $server = $this->createMock(HttpServer::class);
        $server
            ->expects($this->exactly(2))
            ->method('on');
        $callback = function () use ($server) {
            return $server;
        };

        $container = $this->prophesize(ContainerInterface::class);
        $getTaskRunner = $container->get(TaskRunner::class)->willReturn($this->prophesize(TaskRunner::class)->reveal());
        $getLogger = $container->get(LoggerInterface::class)->willReturn(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $result = ($this->delegator)($container->reveal(), '', $callback);

        $this->assertSame($server, $result);
        $getTaskRunner->shouldHaveBeenCalledOnce();
        $getLogger->shouldHaveBeenCalledOnce();
    }
}
