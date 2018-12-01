<?php
declare(strict_types=1);

use Shlinkio\Shlink\Common\Factory\EmptyResponseImplicitOptionsMiddlewareFactory;
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;

return [

    'dependencies' => [
        'factories' => [
            ImplicitOptionsMiddleware::class => EmptyResponseImplicitOptionsMiddlewareFactory::class,
        ],

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
