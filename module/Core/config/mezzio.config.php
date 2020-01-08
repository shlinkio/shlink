<?php

declare(strict_types=1);

return [

    'mezzio' => [
        'error_handler' => [
            'template_404'   => 'ShlinkCore::error/404',
            'template_error' => 'ShlinkCore::error/error',
        ],
    ],

];
