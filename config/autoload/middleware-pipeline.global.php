<?php
use Acelaya\UrlShortener\Middleware\CliParamsMiddleware;
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
                CliParamsMiddleware::class,
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
