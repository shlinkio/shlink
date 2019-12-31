<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Zend\Expressive;
use Zend\ProblemDetails;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'middleware_pipeline' => [
        'error-handler' => [
            'middleware' => [
                Expressive\Helper\ContentLengthMiddleware::class,
                ErrorHandler::class,
            ],
        ],
        'error-handler-rest' => [
            'path' => '/rest',
            'middleware' => [
                Rest\Middleware\CrossDomainMiddleware::class,
                ProblemDetails\ProblemDetailsMiddleware::class,
            ],
        ],

        'pre-routing' => [
            'middleware' => [
                Common\Middleware\CloseDbConnectionMiddleware::class,
            ],
        ],
        'pre-routing-rest' => [
            'path' => '/rest',
            'middleware' => [
                Rest\Middleware\PathVersionMiddleware::class,
            ],
        ],

        'routing' => [
            'middleware' => [
                Expressive\Router\Middleware\RouteMiddleware::class,
            ],
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Expressive\Router\Middleware\ImplicitOptionsMiddleware::class,
                Rest\Middleware\BodyParserMiddleware::class,
                Rest\Middleware\AuthenticationMiddleware::class,
            ],
        ],

        'dispatch' => [
            'middleware' => [
                Expressive\Router\Middleware\DispatchMiddleware::class,
            ],
        ],

        'not-found-rest' => [
            'path' => '/rest',
            'middleware' => [
                ProblemDetails\ProblemDetailsNotFoundHandler::class,
            ],
        ],
        'not-found' => [
            'middleware' => [
                Core\ErrorHandler\NotFoundRedirectHandler::class,
                Core\ErrorHandler\NotFoundTemplateHandler::class,
            ],
        ],
    ],
];
