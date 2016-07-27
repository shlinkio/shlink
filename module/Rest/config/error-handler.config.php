<?php
use Shlinkio\Shlink\Rest\Expressive\JsonErrorHandler;

return [

    'error_handler' => [
        'plugins' => [
            'invokables' => [
                'application/json' => JsonErrorHandler::class,
            ],
            'aliases' => [
                'application/x-json' => 'application/json',
                'text/json' => 'application/json',
            ],
        ],
    ],

];
