<?php

declare(strict_types=1);

use Zend\Expressive;
use Zend\Expressive\Container;

return [

    'dependencies' => [
        'delegators' => [
            Expressive\Application::class => [
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
