<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;

return static function (): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    $app = $container->get(Application::class);

    $app->run();
};
