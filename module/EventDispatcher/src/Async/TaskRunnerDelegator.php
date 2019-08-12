<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher\Async;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as HttpServer;

class TaskRunnerDelegator
{
    public function __invoke(ContainerInterface $container, $name, callable $callback): HttpServer
    {
        /** @var HttpServer $server */
        $server = $callback();
        $logger = $container->get(LoggerInterface::class);

        $server->on('task', $container->get(TaskRunner::class));
        $server->on('finish', function (HttpServer $server, int $taskId) use ($logger) {
            $logger->notice('Task #{taskId} has finished processing', ['taskId' => $taskId]);
        });

        return $server;
    }
}
