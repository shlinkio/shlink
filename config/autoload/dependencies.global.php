<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Application;
use Mezzio\Container;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\Filesystem\Filesystem;

return [

    'dependencies' => [
        'factories' => [
            PSR7Worker::class => ConfigAbstractFactory::class,
            Filesystem::class => InvokableFactory::class,
        ],

        'delegators' => [
            Application::class => [
                Container\ApplicationConfigInjectionDelegator::class,
            ],
        ],

        'aliases' => [
            ClientInterface::class => Client::class,
        ],

        'lazy_services' => [
            'proxies_target_dir' => 'data/proxies',
            'proxies_namespace' => 'ShlinkProxy',
            'write_proxy_files' => EnvVars::isProdEnv(),
        ],
    ],

    ConfigAbstractFactory::class => [
        PSR7Worker::class => [
            WorkerInterface::class,
            ServerRequestFactoryInterface::class,
            StreamFactoryInterface::class,
            UploadedFileFactoryInterface::class,
        ],
    ],

];
