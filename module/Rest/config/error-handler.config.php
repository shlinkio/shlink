<?php

declare(strict_types=1);

use Shlinkio\Shlink\Rest\ErrorHandler\JsonErrorResponseGenerator;

return [

    'error_handler' => [
        'plugins' => [
            'invokables' => [
                'application/json' => JsonErrorResponseGenerator::class,
            ],
            'aliases' => [
                'application/x-json' => 'application/json',
                'text/json' => 'application/json',
            ],
        ],
    ],

];
