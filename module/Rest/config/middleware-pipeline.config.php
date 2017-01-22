<?php
use Shlinkio\Shlink\Rest\Middleware;

return [

    'middleware_pipeline' => [
        'pre-routing' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\PathVersionMiddleware::class,
            ],
            'priority' => 11,
        ],

        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\CrossDomainMiddleware::class,
                Middleware\BodyParserMiddleware::class,
                Middleware\CheckAuthenticationMiddleware::class,
            ],
            'priority' => 5,
        ],
    ],
];
