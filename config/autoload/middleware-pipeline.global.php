<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Zend\Expressive;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'middleware_pipeline' => [
        'pre-routing' => [
            'middleware' => (function () {
                $middleware = [
                    ErrorHandler::class,
                    Expressive\Helper\ContentLengthMiddleware::class,
                ];

                if (Common\Exec\ExecutionContext::currentContextIsSwoole()) {
                    $middleware[] = Common\Middleware\CloseDbConnectionMiddleware::class;
                }

                return $middleware;
            })(),
            'priority' => 12,
        ],
        'pre-routing-rest' => [
            'path' => '/rest',
            'middleware' => [
                Rest\Middleware\PathVersionMiddleware::class,
                Rest\Middleware\ShortUrl\ShortCodePathMiddleware::class,
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
                Rest\Middleware\CrossDomainMiddleware::class,
                Expressive\Router\Middleware\ImplicitOptionsMiddleware::class,
                Rest\Middleware\BodyParserMiddleware::class,
                Rest\Middleware\AuthenticationMiddleware::class,
            ],
            'priority' => 5,
        ],

        'post-routing' => [
            'middleware' => [
                Expressive\Router\Middleware\DispatchMiddleware::class,

                // Only if a not found error is triggered, set-up the locale to be used
                Common\Middleware\LocaleMiddleware::class,
                Core\Response\NotFoundHandler::class,
            ],
            'priority' => 1,
        ],
    ],
];
