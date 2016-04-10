<?php

return [
    'debug' => false,

    'config_cache_enabled' => false,

    'zend-expressive' => [
        'error_handler' => [
            'template_404'   => 'error/404.html.twig',
            'template_error' => 'error/error.html.twig',
        ],
    ],
];
