<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;
use Symfony\Component\Process\Process;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

$shlinkDbPath = realpath(sys_get_temp_dir()) . '/shlink-tests.db';
if (file_exists($shlinkDbPath)) {
    unlink($shlinkDbPath);
}

/** @var ContainerInterface $container */
$container = require __DIR__ . '/config/test-container.php';

// Create database
$process = new Process(['vendor/bin/doctrine', 'orm:schema-tool:create', '--no-interaction', '-q', '--test'], __DIR__);
$process->inheritEnvironmentVariables()
        ->mustRun();

DatabaseTestCase::$em = $container->get('em');
