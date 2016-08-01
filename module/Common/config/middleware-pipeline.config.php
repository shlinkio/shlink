<?php
use Shlinkio\Shlink\Common\Middleware;

return [

    'middleware_pipeline' => [
        'pre-routing' => [
            'middleware' => [
                Middleware\LocaleMiddleware::class,
            ],
            'priority' => 5,
        ],
    ],

];
