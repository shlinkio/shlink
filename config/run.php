<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as CliApp;
use Zend\Expressive\Application;

return function (bool $isCli = false): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    $app = $container->get($isCli ? CliApp::class : Application::class);

    $app->run();
};
