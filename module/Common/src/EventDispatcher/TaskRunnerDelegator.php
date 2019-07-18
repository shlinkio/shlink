<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as HttpServer;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class TaskRunnerDelegator implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        array $options = null
    ): HttpServer {
        $server = $callback();
        $logger = $container->get(LoggerInterface::class);

        $server->on('task', $container->get(TaskRunner::class));
        $server->on('finish', function (HttpServer $server, int $taskId) use ($logger) {
            $logger->notice('Task #{taskId} has finished processing', ['taskId' => $taskId]);
        });

        return $server;
    }
}
