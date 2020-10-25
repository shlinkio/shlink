<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Mezzio\Container;
use Psr\Http\Client\ClientInterface;

return [

    'dependencies' => [
        'delegators' => [
            Mezzio\Application::class => [
                Container\ApplicationConfigInjectionDelegator::class,
            ],
        ],

        'aliases' => [
            ClientInterface::class => Client::class,
        ],

        'lazy_services' => [
            'proxies_target_dir' => 'data/proxies',
            'proxies_namespace' => 'ShlinkProxy',
            'write_proxy_files' => true,
        ],
    ],

];
