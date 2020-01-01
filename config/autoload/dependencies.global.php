<?php

declare(strict_types=1);

use Mezzio\Container;

return [

    'dependencies' => [
        'delegators' => [
            Mezzio\Application::class => [
                Container\ApplicationConfigInjectionDelegator::class,
            ],
        ],

        'lazy_services' => [
            'proxies_target_dir' => 'data/proxies',
            'proxies_namespace' => 'ShlinkProxy',
            'write_proxy_files' => true,
        ],
    ],

];
