<?php
use Acelaya\UrlShortener\Middleware;
use Zend\Expressive\Container\ApplicationFactory;
use Zend\Expressive\Helper;

return [

    'middleware_pipeline' => [
        'always' => [
            'middleware' => [
                Helper\ServerUrlMiddleware::class,
            ],
            'priority' => 10000,
        ],

        'routing' => [
            'middleware' => [
                ApplicationFactory::ROUTING_MIDDLEWARE,
            ],
            'priority' => 10,
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\CheckAuthenticationMiddleware::class,
                Middleware\CrossDomainMiddleware::class,
            ],
            'priority' => 5,
        ],

        'post-routing' => [
            'middleware' => [
                Helper\UrlHelperMiddleware::class,
                ApplicationFactory::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],

        'error' => [
            'middleware' => [],
            'error'    => true,
            'priority' => -10000,
        ],
    ],
];
