<?php
namespace Acelaya\UrlShortener\CLI\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Symfony\Component\Console\Application as CliApp;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApplicationFactory implements FactoryInterface
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
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['cli'];
        $app = new CliApp();

        $commands = isset($config['commands']) ? $config['commands'] : [];
        foreach ($commands as $command) {
            if (! $container->has($command)) {
                continue;
            }

            $app->add($container->get($command));
        }

        return $app;
    }
}
