<?php

declare(strict_types=1);

use Symfony\Component\Lock;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

// This class alias tricks the ConfigAbstractFactory to return Lock\Factory instances even with a different service name
class_alias(Lock\LockFactory::class, 'Shlinkio\Shlink\LocalLockFactory');

// Build container
$config = require __DIR__ . '/config.php';
$container = new ServiceManager($config['dependencies']);
$container->setService('config', $config);
return $container;
