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
                ErrorHandler::class,
            ],
        ],
        'error-handler-rest' => [
            'path' => '/rest',
            'middleware' => [
                ProblemDetails\ProblemDetailsMiddleware::class,
            ],
        ],

        'pre-routing' => [
            'middleware' => [
                Expressive\Helper\ContentLengthMiddleware::class,
                Common\Middleware\CloseDbConnectionMiddleware::class,
            ],
        ],
        'pre-routing-rest' => [
            'path' => '/rest',
            'middleware' => [
                Rest\Middleware\PathVersionMiddleware::class,
                Rest\Middleware\ShortUrl\ShortCodePathMiddleware::class,
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
                Rest\Middleware\CrossDomainMiddleware::class,
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
