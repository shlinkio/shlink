<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\EventDispatcher\Async;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\EventDispatcher\Async\TaskInterface;
use Shlinkio\Shlink\EventDispatcher\Async\TaskRunner;
use Swoole\Http\Server as HttpServer;

class TaskRunnerTest extends TestCase
{
    /** @var TaskRunner */
    private $taskRunner;
    /** @var ObjectProphecy */
    private $logger;
    /** @var ObjectProphecy */
    private $container;
    /** @var ObjectProphecy */
    private $server;
    /** @var ObjectProphecy */
    private $task;

    public function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->task = $this->prophesize(TaskInterface::class);

        $this->server = $this->createMock(HttpServer::class);
        $this->server
            ->expects($this->once())
            ->method('finish')
            ->with('');

        $this->taskRunner = new TaskRunner($this->logger->reveal(), $this->container->reveal());
    }

    /** @test */
    public function warningIsLoggedWhenProvidedTaskIsInvalid(): void
    {
        $logWarning = $this->logger->warning('Invalid task provided to task worker: {type}. Task ignored', [
            'type' => 'string',
        ]);
        $logInfo = $this->logger->info(Argument::cetera());
        $logError = $this->logger->error(Argument::cetera());

        ($this->taskRunner)($this->server, 1, 1, 'invalid_task');

        $logWarning->shouldHaveBeenCalledOnce();
        $logInfo->shouldNotHaveBeenCalled();
        $logError->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function properTasksAreRun(): void
    {
        $logWarning = $this->logger->warning(Argument::cetera());
        $logInfo = $this->logger->notice('Starting work on task {taskId}: {task}', [
            'taskId' => 1,
            'task' => 'The task',
        ]);
        $logError = $this->logger->error(Argument::cetera());
        $taskToString = $this->task->toString()->willReturn('The task');
        $taskRun = $this->task->run($this->container->reveal())->will(function () {
        });

        ($this->taskRunner)($this->server, 1, 1, $this->task->reveal());

        $logWarning->shouldNotHaveBeenCalled();
        $logInfo->shouldHaveBeenCalledOnce();
        $logError->shouldNotHaveBeenCalled();
        $taskToString->shouldHaveBeenCalledOnce();
        $taskRun->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function errorIsLoggedWhenTasksFail(): void
    {
        $e = new Exception('Error');

        $logWarning = $this->logger->warning(Argument::cetera());
        $logInfo = $this->logger->notice('Starting work on task {taskId}: {task}', [
            'taskId' => 1,
            'task' => 'The task',
        ]);
        $logError = $this->logger->error('Error processing task {taskId}: {e}', [
            'taskId' => 1,
            'e' => $e,
        ]);
        $taskToString = $this->task->toString()->willReturn('The task');
        $taskRun = $this->task->run($this->container->reveal())->willThrow($e);

        ($this->taskRunner)($this->server, 1, 1, $this->task->reveal());

        $logWarning->shouldNotHaveBeenCalled();
        $logInfo->shouldHaveBeenCalledOnce();
        $logError->shouldHaveBeenCalledOnce();
        $taskToString->shouldHaveBeenCalledOnce();
        $taskRun->shouldHaveBeenCalledOnce();
    }
}
