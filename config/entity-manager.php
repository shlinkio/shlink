<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

return (static function () {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    return $container->get(EntityManager::class);
})();
