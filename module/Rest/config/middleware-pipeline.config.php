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

        'rest-error' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\Error\ResponseTypeMiddleware::class,
            ],
            'error'    => true,
            'priority' => -10000,
        ],
    ],
];
