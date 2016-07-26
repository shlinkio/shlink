<?php
use Shlinkio\Shlink\Rest\Middleware;

return [

    'middleware_pipeline' => [
        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\CheckAuthenticationMiddleware::class,
                Middleware\CrossDomainMiddleware::class,
            ],
            'priority' => 5,
        ],

        'rest-not-found' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\NotFoundMiddleware::class,
            ],
            'priority' => -1,
        ],
    ],
];
