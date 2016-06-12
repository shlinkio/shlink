<?php
use Acelaya\UrlShortener\Middleware\Routable;
use Acelaya\UrlShortener\Middleware\Rest;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => Routable\RedirectMiddleware::class,
            'allowed_methods' => ['GET'],
        ],

        // Rest
        [
            'name' => 'rest-create-shortcode',
            'path' => '/rest/short-code',
            'middleware' => Rest\CreateShortcodeMiddleware::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => 'rest-resolve-url',
            'path' => '/rest/short-code/{shortCode}',
            'middleware' => Rest\ResolveUrlMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
