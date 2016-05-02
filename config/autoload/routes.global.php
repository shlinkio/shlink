<?php
use Acelaya\UrlShortener\Middleware\Routable;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => Routable\RedirectMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
