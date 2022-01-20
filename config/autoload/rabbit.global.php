<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'rabbitmq' => [
        'enabled' => (bool) EnvVars::RABBITMQ_ENABLED()->loadFromEnv(false),
        'host' => EnvVars::RABBITMQ_HOST()->loadFromEnv(),
        'port' => (int) EnvVars::RABBITMQ_PORT()->loadFromEnv('5672'),
        'user' => EnvVars::RABBITMQ_USER()->loadFromEnv(),
        'password' => EnvVars::RABBITMQ_PASSWORD()->loadFromEnv(),
        'vhost' => EnvVars::RABBITMQ_VHOST()->loadFromEnv('/'),
    ],

    'dependencies' => [
        'factories' => [
            AMQPStreamConnection::class => ConfigAbstractFactory::class,
        ],
        'delegators' => [
            AMQPStreamConnection::class => [
                LazyServiceFactory::class,
            ],
        ],
        'lazy_services' => [
            'class_map' => [
                AMQPStreamConnection::class => AMQPStreamConnection::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        AMQPStreamConnection::class => [
            'config.rabbitmq.host',
            'config.rabbitmq.port',
            'config.rabbitmq.user',
            'config.rabbitmq.password',
            'config.rabbitmq.vhost',
        ],
    ],

];
