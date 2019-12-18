<?php

declare(strict_types=1);

use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

// Build container
$config = require __DIR__ . '/config.php';
$container = new ServiceManager($config['dependencies']);
$container->setService('config', $config);
return $container;
