<?php
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        [
            'name' => 'rest-authenticate',
            'path' => '/rest/v{version:1}/authenticate',
            'middleware' => Action\AuthenticateAction::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => 'rest-create-shortcode',
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\CreateShortcodeAction::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => 'rest-resolve-url',
            'path' => '/rest/v{version:1}/short-codes/{shortCode}',
            'middleware' => Action\ResolveUrlAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-list-shortened-url',
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\ListShortcodesAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-get-visits',
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/visits',
            'middleware' => Action\GetVisitsAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'rest-edit-tags',
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/tags',
            'middleware' => Action\EditTagsAction::class,
            'allowed_methods' => ['PUT'],
        ],
    ],

];
