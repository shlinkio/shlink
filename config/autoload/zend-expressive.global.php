<?php

return [
    'debug' => false,

    'config_cache_enabled' => true,

    'zend-expressive' => [
        'error_handler' => [
            'template_404'   => 'error/404.html.twig',
            'template_error' => 'error/error.html.twig',
        ],
    ],
];
