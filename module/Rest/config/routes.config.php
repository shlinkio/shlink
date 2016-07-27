<?php
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        [
            'name' => 'rest-authenticate',
            'path' => '/rest/authenticate',
            'middleware' => Action\AuthenticateMiddleware::class,
            'allowed_methods' => ['POST', 'OPTIONS'],
        ],
        [
            'name' => 'rest-create-shortcode',
            'path' => '/rest/short-codes',
            'middleware' => Action\CreateShortcodeMiddleware::class,
            'allowed_methods' => ['POST', 'OPTIONS'],
        ],
        [
            'name' => 'rest-resolve-url',
            'path' => '/rest/short-codes/{shortCode}',
            'middleware' => Action\ResolveUrlMiddleware::class,
            'allowed_methods' => ['GET', 'OPTIONS'],
        ],
        [
            'name' => 'rest-list-shortened-url',
            'path' => '/rest/short-codes',
            'middleware' => Action\ListShortcodesMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-get-visits',
            'path' => '/rest/short-codes/{shortCode}/visits',
            'middleware' => Action\GetVisitsMiddleware::class,
            'allowed_methods' => ['GET', 'OPTIONS'],
        ],
    ],

];
