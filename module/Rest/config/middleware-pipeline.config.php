<?php
use Shlinkio\Shlink\Rest\Middleware;

return [

    'middleware_pipeline' => [
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
