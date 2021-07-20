<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as CliApp;

return (static function () {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    return $container->get(CliApp::class);
})();
