<?php
declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceManager;

// If the "--test" flag was provided, we are on a test environment
$isTest = false;
foreach ($_SERVER['argv'] as $i => $arg) {
    if ($arg === '--test') {
        unset($_SERVER['argv'][$i]);
        $isTest = true;
        break;
    }
}

/** @var ContainerInterface|ServiceManager $container */
$container = $isTest ? include __DIR__ . '/test-container.php' : include __DIR__ . '/container.php';
$em = $container->get(EntityManager::class);

return ConsoleRunner::createHelperSet($em);
