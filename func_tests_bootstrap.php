<?php
declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;
use Symfony\Component\Process\Process;
use Zend\ServiceManager\ServiceManager;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

/** @var ServiceManager $sm */
$sm = require __DIR__ . '/config/container.php';
$sm->setAllowOverride(true);
$config = $sm->get('config');
$config['entity_manager']['connection'] = [
    'driver' => 'pdo_sqlite',
    'memory' => true,
];
$sm->setService('config', $config);

$process = new Process('vendor/bin/doctrine-migrations migrations:migrate --no-interaction -q', __DIR__);
$process->inheritEnvironmentVariables()
        ->setTimeout(60 * 5) // 5 minutes
        ->mustRun();

DatabaseTestCase::$em = $sm->get(EntityManagerInterface::class);
