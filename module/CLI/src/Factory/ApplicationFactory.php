<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Symfony\Component\Console\Application as CliApp;
use Zend\I18n\Translator\Translator;
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
        $appOptions = $container->get(AppOptions::class);
        $translator = $container->get(Translator::class);
        $translator->setLocale($config['locale']);

        $commands = isset($config['commands']) ? $config['commands'] : [];
        $app = new CliApp($appOptions->getName(), $appOptions->getVersion());
        foreach ($commands as $command) {
            if (! $container->has($command)) {
                continue;
            }

            $app->add($container->get($command));
        }

        return $app;
    }
}
