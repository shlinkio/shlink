<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PhpMiddleware\RequestId;

return [

    'request_id' => [
        'allow_override' => true,
        'header_name' => 'X-Request-Id',
    ],

    'dependencies' => [
        'factories' => [
            RequestId\Generator\RamseyUuid4StaticGenerator::class => InvokableFactory::class,
            RequestId\RequestIdProviderFactory::class => ConfigAbstractFactory::class,
            RequestId\RequestIdMiddleware::class => ConfigAbstractFactory::class,
            RequestId\MonologProcessor::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        RequestId\RequestIdProviderFactory::class => [
            RequestId\Generator\RamseyUuid4StaticGenerator::class,
            'config.request_id.allow_override',
            'config.request_id.header_name',
        ],
        RequestId\RequestIdMiddleware::class => [
            RequestId\RequestIdProviderFactory::class,
            'config.request_id.header_name',
        ],
        RequestId\MonologProcessor::class => [RequestId\RequestIdMiddleware::class],
    ],

];
