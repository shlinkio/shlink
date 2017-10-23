<?php
declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceManager;

$isTest = false;
foreach ($_SERVER['argv'] as $i => $arg) {
    if ($arg === '--test') {
        unset($_SERVER['argv'][$i]);
        $isTest = true;
        break;
    }
}

/** @var ContainerInterface|ServiceManager $container */
$container = include __DIR__ . '/container.php';

// If in testing env, override DB connection to use an in-memory sqlite database
if ($isTest) {
    $container->setAllowOverride(true);
    $config = $container->get('config');
    $config['entity_manager']['connection'] = [
        'driver' => 'pdo_sqlite',
        'path' => realpath(sys_get_temp_dir()) . '/shlink-tests.db',
    ];
    $container->setService('config', $config);
}

/** @var EntityManager $em */
$em = $container->get(EntityManager::class);

return ConsoleRunner::createHelperSet($em);
