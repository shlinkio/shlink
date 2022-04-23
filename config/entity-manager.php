<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

return (static function (): EntityManagerInterface {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/container.php';
    return $container->get(EntityManager::class);
})();
