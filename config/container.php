<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Lock;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

// If the Dotenv class exists, load env vars and enable errors
if (class_exists(Dotenv::class)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/../.env');
}

// This class alias tricks the ConfigAbstractFactory to return Lock\Factory instances even with a different service name
class_alias(Lock\Factory::class, 'Shlinkio\Shlink\LocalLockFactory');

// Build container
$config = require __DIR__ . '/config.php';
$container = new ServiceManager($config['dependencies']);
$container->setService('config', $config);
return $container;
