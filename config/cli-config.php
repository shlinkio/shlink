<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

return (function () {
    /** @var ContainerInterface|ServiceManager $container */
    $container = include __DIR__ . '/container.php';
    $em = $container->get(EntityManager::class);

    return ConsoleRunner::createHelperSet($em);
})();
