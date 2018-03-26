<?php
declare(strict_types=1);

use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Core\Response\NotFoundDelegate;
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
//            'path' => '/rest',
            'middleware' => [
                PathVersionMiddleware::class,
            ],
            'priority' => 11,
        ],

        'routing' => [
            'middleware' => [
                Expressive\Router\Middleware\RouteMiddleware::class,
            ],
            'priority' => 10,
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                CrossDomainMiddleware::class,
                Expressive\Router\Middleware\ImplicitOptionsMiddleware::class,
                BodyParserMiddleware::class,
                CheckAuthenticationMiddleware::class,
            ],
            'priority' => 5,
        ],

        'post-routing' => [
            'middleware' => [
                Expressive\Router\Middleware\DispatchMiddleware::class,
                NotFoundDelegate::class,
            ],
            'priority' => 1,
        ],
    ],
];
