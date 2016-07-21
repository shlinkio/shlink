<?php
use Shlinkio\Shlink\Rest\Middleware;

return [

    'middleware_pipeline' => [
        'rest' => [
            'path' => '/rest',
            'middleware' => [
                Middleware\CheckAuthenticationMiddleware::class,
                Middleware\LocaleMiddleware::class,
                Middleware\CrossDomainMiddleware::class,
            ],
            'priority' => 5,
        ],
    ],
];
