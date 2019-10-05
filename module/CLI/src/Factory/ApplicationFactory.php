<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Factory;

use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Symfony\Component\Console\Application as CliApp;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): CliApp
    {
        $config = $container->get('config')['cli'];
        $appOptions = $container->get(AppOptions::class);

        $commands = $config['commands'] ?? [];
        $app = new CliApp($appOptions->getName(), $appOptions->getVersion());
        $app->setCommandLoader(new ContainerCommandLoader($container, $commands));

        return $app;
    }
}
