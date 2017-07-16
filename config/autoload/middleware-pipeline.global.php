<?php
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;
use Shlinkio\Shlink\Rest\Middleware\CheckAuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;
use Shlinkio\Shlink\Rest\Middleware\PathVersionMiddleware;
use Zend\Expressive;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'middleware_pipeline' => [
        'pre-routing' => [
            'middleware' => [
                ErrorHandler::class,
                LocaleMiddleware::class,
            ],
            'priority' => 11,
        ],
        'pre-routing-rest' => [
            'path' => '/rest',
            'middleware' => [
                PathVersionMiddleware::class,
            ],
            'priority' => 11,
        ],

        'routing' => [
            'middleware' => [
                Expressive\Application::ROUTING_MIDDLEWARE,
            ],
            'priority' => 10,
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                CrossDomainMiddleware::class,
                Expressive\Middleware\ImplicitOptionsMiddleware::class,
                BodyParserMiddleware::class,
                CheckAuthenticationMiddleware::class,
            ],
            'priority' => 5,
        ],

        'post-routing' => [
            'middleware' => [
                Expressive\Application::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],
    ],
];
