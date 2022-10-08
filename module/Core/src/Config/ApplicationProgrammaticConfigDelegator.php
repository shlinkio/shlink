<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

use function file_exists;

class ApplicationProgrammaticConfigDelegator
{
    private const PROGRAMMATIC_CONFIG_FILES = [
        __DIR__ . '/../../../../config/programmatic/pipeline.php',
        __DIR__ . '/../../../../config/programmatic/routes.php',
    ];

    public function __invoke(ContainerInterface $container, string $name, callable $callback): Application
    {
        /** @var Application $app */
        $app = $callback();
        /** @var MiddlewareFactory $factory */
        $factory = $container->get(MiddlewareFactory::class);

        foreach (self::PROGRAMMATIC_CONFIG_FILES as $configFile) {
            if (file_exists($configFile)) {
                (require $configFile)($app, $factory, $container);
            }
        }

        return $app;
    }
}
