<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Logger;

use Psr\Container\ContainerInterface;
use Psr\Log;

class LoggerAwareDelegatorFactory
{
    public function __invoke(ContainerInterface $container, $name, callable $callback)
    {
        $instance = $callback();
        if ($instance instanceof Log\LoggerAwareInterface) {
            $instance->setLogger($container->get(Log\LoggerInterface::class));
        }

        return $instance;
    }
}
