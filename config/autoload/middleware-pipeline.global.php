<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\ProblemDetails;
use Mezzio\Router;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Common\Middleware\AccessLogMiddleware;
use Shlinkio\Shlink\Common\Middleware\ContentLengthMiddleware;
use Shlinkio\Shlink\Common\Middleware\RequestIdMiddleware;
use Shlinkio\Shlink\Core\Geolocation\Middleware\IpGeolocationMiddleware;

return [

    'middleware_pipeline' => [
        'error-handler' => [
            'middleware' => [
                AccessLogMiddleware::class,
                ContentLengthMiddleware::class,
                RequestIdMiddleware::class,
                ErrorHandler::class,
                Rest\Middleware\CrossDomainMiddleware::class,
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
                // These two middlewares are in front of other tracking actions.
                // Putting them here for orphan visits tracking
                IpAddress::class,
                IpGeolocationMiddleware::class,

                Core\ErrorHandler\NotFoundTypeResolverMiddleware::class,
                Core\ShortUrl\Middleware\ExtraPathRedirectMiddleware::class,
                Core\ErrorHandler\NotFoundTrackerMiddleware::class,
                Core\ErrorHandler\NotFoundRedirectHandler::class,
                Core\ErrorHandler\NotFoundTemplateHandler::class,
            ],
        ],
    ],

];
