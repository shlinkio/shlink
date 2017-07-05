<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Shlinkio\Shlink\CLI\Command\Install\InstallCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Config\Writer\PhpArray;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class InstallApplicationFactory implements FactoryInterface
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
        $isUpdate = $options !== null && isset($options['isUpdate']) ? (bool) $options['isUpdate'] : false;

        $app = new Application();
        $command = new InstallCommand(new PhpArray(), new Filesystem(), $isUpdate);
        $app->add($command);
        $app->setDefaultCommand($command->getName());

        return $app;
    }
}
