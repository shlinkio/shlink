<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Helper;
use Mezzio\ProblemDetails;
use Mezzio\Router;
use PhpMiddleware\RequestId\RequestIdMiddleware;

return [

    'middleware_pipeline' => [
        'error-handler' => [
            'middleware' => [
                Helper\ContentLengthMiddleware::class,
                ErrorHandler::class,
            ],
        ],
        'error-handler-rest' => [
            'path' => '/rest',
            'middleware' => [
                Rest\Middleware\CrossDomainMiddleware::class,
                RequestIdMiddleware::class,
                ProblemDetails\ProblemDetailsMiddleware::class,
            ],
        ],

        'pre-routing' => [
            'middleware' => [
                Common\Middleware\CloseDbConnectionMiddleware::class,
            ],
        ],

        'routing' => [
            'middleware' => [
                Router\Middleware\RouteMiddleware::class,
                Router\Middleware\ImplicitHeadMiddleware::class,
            ],
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Router\Middleware\ImplicitOptionsMiddleware::class,
                Rest\Middleware\BodyParserMiddleware::class,
                Rest\Middleware\AuthenticationMiddleware::class,
            ],
        ],

        'dispatch' => [
            'middleware' => [
                Router\Middleware\DispatchMiddleware::class,
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
