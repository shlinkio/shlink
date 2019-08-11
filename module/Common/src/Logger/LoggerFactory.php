<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Logger;

use Cascade\Cascade;
use Interop\Container\ContainerInterface;
use Monolog\Logger;

use function count;
use function explode;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): Logger
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Cascade::fileConfig($config['logger'] ?? ['loggers' => []]);

        // Compose requested logger name
        $loggerName = $options['logger_name'] ?? 'Logger';
        $nameParts = explode('_', $requestedName);
        if (count($nameParts) > 1) {
            $loggerName = $nameParts[1];
        }

        return Cascade::getLogger($loggerName);
    }
}
