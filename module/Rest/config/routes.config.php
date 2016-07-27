<?php
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        [
            'name' => 'rest-authenticate',
            'path' => '/rest/authenticate',
            'middleware' => Action\AuthenticateAction::class,
            'allowed_methods' => ['POST', 'OPTIONS'],
        ],
        [
            'name' => 'rest-create-shortcode',
            'path' => '/rest/short-codes',
            'middleware' => Action\CreateShortcodeAction::class,
            'allowed_methods' => ['POST', 'OPTIONS'],
        ],
        [
            'name' => 'rest-resolve-url',
            'path' => '/rest/short-codes/{shortCode}',
            'middleware' => Action\ResolveUrlAction::class,
            'allowed_methods' => ['GET', 'OPTIONS'],
        ],
        [
            'name' => 'rest-list-shortened-url',
            'path' => '/rest/short-codes',
            'middleware' => Action\ListShortcodesAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-get-visits',
            'path' => '/rest/short-codes/{shortCode}/visits',
            'middleware' => Action\GetVisitsAction::class,
            'allowed_methods' => ['GET', 'OPTIONS'],
        ],
    ],

];
