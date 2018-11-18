<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Symfony\Component\Console\Application as CliApp;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
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
     * @return CliApp
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CliApp
    {
        $config = $container->get('config')['cli'];
        $appOptions = $container->get(AppOptions::class);

        $commands = $config['commands'] ?? [];
        $app = new CliApp($appOptions->getName(), $appOptions->getVersion());
        $app->setCommandLoader(new ContainerCommandLoader($container, $commands));

        return $app;
    }
}
