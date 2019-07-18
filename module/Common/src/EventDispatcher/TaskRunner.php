<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as HttpServer;
use Throwable;

use function get_class;
use function gettype;
use function is_object;

class TaskRunner
{
    /** @var LoggerInterface */
    private $logger;
    /** @var ContainerInterface */
    private $container;

    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->container = $container;
    }

    public function __invoke(HttpServer $server, int $taskId, int $fromId, $task): void
    {
        if (! $task instanceof Task) {
            $this->logger->error('Invalid task provided to task worker: {type}', [
                'type' => is_object($task) ? get_class($task) : gettype($task),
            ]);
            $server->finish('');
            return;
        }

        $this->logger->notice('Starting work on task {taskId}: {task}', [
            'taskId' => $taskId,
            'task' => $task->toString(),
        ]);

        try {
            $task($this->container);
        } catch (Throwable $e) {
            $this->logger->error('Error processing task {taskId}: {e}', [
                'taskId' => $taskId,
                'e' => $e,
            ]);
        } finally {
            // Notify the server that processing of the task has finished:
            $server->finish('');
        }
    }
}
