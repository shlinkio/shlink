<?php
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Interop\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/config/container.php';
/** @var EntityManager $em */
$em = $container->get(EntityManager::class);

return ConsoleRunner::createHelperSet($em);
