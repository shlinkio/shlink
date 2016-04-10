<?php
use Zend\Expressive\Application;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

// Build container
$config = require __DIR__ . '/config.php';
$container = new ServiceManager($config['services']);
$container->setService('config', $config);

return $container->get(Application::class);
