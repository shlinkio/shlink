<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Logger\Swoole;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Zend\Expressive\Swoole\Log;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class AccessLogFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['zend-expressive-swoole']['swoole-http-server']['logger'] ?? [];

        return new Log\Psr3AccessLogDecorator(
            $this->getLogger($container, $config),
            $this->getFormatter($container, $config),
            $config['use-hostname-lookups'] ?? false
        );
    }

    private function getLogger(ContainerInterface $container, array $config): LoggerInterface
    {
        $loggerName = $config['logger_name'] ?? LoggerInterface::class;
        return $container->has($loggerName) ? $container->get($loggerName) : new Log\StdoutLogger();
    }

    private function getFormatter(ContainerInterface $container, array $config): Log\AccessLogFormatterInterface
    {
        if ($container->has(Log\AccessLogFormatterInterface::class)) {
            return $container->get(Log\AccessLogFormatterInterface::class);
        }

        return new Log\AccessLogFormatter($config['format'] ?? Log\AccessLogFormatter::FORMAT_COMMON);
    }
}
