<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;

(function () {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';
    $container->get(Application::class)->run();
})();
