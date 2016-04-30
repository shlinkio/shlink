<?php
use Acelaya\UrlShortener\Middleware\CliRoutable;

return [

    'routes' => [
        [
            'name' => 'cli-generate-shortcode',
            'path' => '/generate-shortcode',
            'middleware' => CliRoutable\GenerateShortcodeMiddleware::class,
            'allowed_methods' => ['CLI'],
        ],
    ],

];
