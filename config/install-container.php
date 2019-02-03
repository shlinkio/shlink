<?php
declare(strict_types=1);

use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Installer\Config\Plugin;
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
        'random-chars-generator' => function () {
            return str_shuffle(UrlShortenerOptions::DEFAULT_CHARS);
        },
        'config' => [
            ConfigAbstractFactory::class => [
                Plugin\DatabaseConfigCustomizer::class => [Filesystem::class],
                Plugin\UrlShortenerConfigCustomizer::class => ['random-chars-generator'],
            ],
        ],
    ],
]);
return $container;
