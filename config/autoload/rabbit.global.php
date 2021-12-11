<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;

use function Shlinkio\Shlink\Common\env;

return [

    'rabbit' => [
        'enabled' => (bool) env('RABBITMQ_ENABLED', false),
        'host' => env('RABBITMQ_HOST'),
        'port' => env('RABBITMQ_PORT', '5672'),
        'user' => env('RABBITMQ_USER'),
        'password' => env('RABBITMQ_PASSWORD'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
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
            'config.rabbit.host',
            'config.rabbit.port',
            'config.rabbit.user',
            'config.rabbit.password',
            'config.rabbit.vhost',
        ],
    ],

];
