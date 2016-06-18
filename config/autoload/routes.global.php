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
            'path' => '/rest/short-codes',
            'middleware' => Rest\CreateShortcodeMiddleware::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => 'rest-resolve-url',
            'path' => '/rest/short-codes/{shortCode}',
            'middleware' => Rest\ResolveUrlMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-list-shortened-url',
            'path' => '/rest/short-codes',
            'middleware' => Rest\ListShortcodesMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-get-visits',
            'path' => '/rest/visits/{shortCode}',
            'middleware' => Rest\GetVisitsMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
