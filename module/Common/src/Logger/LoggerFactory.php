<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Logger;

use Cascade\Cascade;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

use function count;
use function explode;

class LoggerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
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
