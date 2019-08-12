<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher\Async;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class TaskRunnerFactory
{
    public function __invoke(ContainerInterface $container): TaskRunner
    {
        $logger = $container->get(LoggerInterface::class);
        return new TaskRunner($logger, $container);
    }
}
