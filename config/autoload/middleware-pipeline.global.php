<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Helper;
use Mezzio\ProblemDetails;
use Mezzio\Router;
use PhpMiddleware\RequestId\RequestIdMiddleware;
use RKA\Middleware\IpAddress;

use function extension_loaded;

return [

    'middleware_pipeline' => [
        'error-handler' => [
            'middleware' => [
                // For some reason, with swoole 4.6.3, piping this middleware makes requests to have incomplete body or
                // never finish loading. Disabling it for swoole fixes it as it already calculates the header on itself
                ...extension_loaded('swoole') ? [] : [Helper\ContentLengthMiddleware::class],
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
                // This middleware is in front of tracking actions explicitly. Putting here for orphan visits tracking
                IpAddress::class,
                Core\ErrorHandler\NotFoundTypeResolverMiddleware::class,
                Core\ErrorHandler\NotFoundTrackerMiddleware::class,
                Core\ErrorHandler\NotFoundRedirectHandler::class,
                Core\ErrorHandler\NotFoundTemplateHandler::class,
            ],
        ],
    ],

];
