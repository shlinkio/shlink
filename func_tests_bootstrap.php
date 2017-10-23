<?php
declare(strict_types=1);

use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;
use Symfony\Component\Process\Process;
use Zend\ServiceManager\ServiceManager;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

$shlinkDbPath = realpath(sys_get_temp_dir()) . '/shlink-tests.db';
if (file_exists($shlinkDbPath)) {
    unlink($shlinkDbPath);
}

/** @var ServiceManager $sm */
$sm = require __DIR__ . '/config/container.php';
$sm->setAllowOverride(true);
$config = $sm->get('config');
$config['entity_manager']['connection'] = [
    'driver' => 'pdo_sqlite',
    'path' => $shlinkDbPath,
];
$sm->setService('config', $config);

// Create database
$process = new Process('vendor/bin/doctrine orm:schema-tool:create --no-interaction -q --test', __DIR__);
$process->inheritEnvironmentVariables()
        ->mustRun();

DatabaseTestCase::$em = $sm->get('em');
