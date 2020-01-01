<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as CliApp;

return function (bool $isCli = false): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    $app = $container->get($isCli ? CliApp::class : Application::class);

    $app->run();
};
