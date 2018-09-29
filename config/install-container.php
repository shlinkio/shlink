<?php
declare(strict_types=1);

use Shlinkio\Shlink\Installer\Config\Plugin\DatabaseConfigCustomizer;
use Shlinkio\Shlink\Installer\Factory\InstallApplicationFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

$container = new ServiceManager([
    'factories' => [
        Application::class => InstallApplicationFactory::class,
        Filesystem::class => InvokableFactory::class,
    ],
    'services' => [
        'config' => [
            ConfigAbstractFactory::class => [
                DatabaseConfigCustomizer::class => [Filesystem::class],
            ],
        ],
    ],
]);
return $container;
