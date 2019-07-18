<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TaskRunnerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TaskRunner
    {
        $logger = $container->get(LoggerInterface::class);
        return new TaskRunner($logger, $container);
    }
}
