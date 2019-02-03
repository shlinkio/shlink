<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Shlinkio\Shlink\Installer\Command\InstallCommand;
use Shlinkio\Shlink\Installer\Config\ConfigCustomizerManager;
use Shlinkio\Shlink\Installer\Config\Plugin;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Config\Writer\PhpArray;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;

class InstallApplicationFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws LogicException
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $isUpdate = $options !== null && isset($options['isUpdate']) ? (bool) $options['isUpdate'] : false;

        $app = new Application();
        $command = new InstallCommand(
            new PhpArray(),
            $container->get(Filesystem::class),
            new ConfigCustomizerManager($container, ['factories' => [
                Plugin\DatabaseConfigCustomizer::class => ConfigAbstractFactory::class,
                Plugin\UrlShortenerConfigCustomizer::class => ConfigAbstractFactory::class,
                Plugin\LanguageConfigCustomizer::class => InvokableFactory::class,
                Plugin\ApplicationConfigCustomizer::class => InvokableFactory::class,
            ]]),
            $isUpdate
        );
        $app->add($command);
        $app->setDefaultCommand($command->getName(), true);

        return $app;
    }
}
