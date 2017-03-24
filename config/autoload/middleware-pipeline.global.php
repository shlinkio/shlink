<?php
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;
use Shlinkio\Shlink\Rest\Middleware\CheckAuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;
use Shlinkio\Shlink\Rest\Middleware\PathVersionMiddleware;
use Zend\Expressive\Container\ApplicationFactory;
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
                ApplicationFactory::ROUTING_MIDDLEWARE,
            ],
            'priority' => 10,
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                CrossDomainMiddleware::class,
                BodyParserMiddleware::class,
                CheckAuthenticationMiddleware::class,
            ],
            'priority' => 5,
        ],

        'post-routing' => [
            'middleware' => [
                ApplicationFactory::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],
    ],
];
