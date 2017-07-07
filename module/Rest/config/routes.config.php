<?php
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        [
            'name' => Action\AuthenticateAction::class,
            'path' => '/rest/v{version:1}/authenticate',
            'middleware' => Action\AuthenticateAction::class,
            'allowed_methods' => ['POST'],
        ],

        // Short codes
        [
            'name' => Action\CreateShortcodeAction::class,
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\CreateShortcodeAction::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => Action\ResolveUrlAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}',
            'middleware' => Action\ResolveUrlAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => Action\ListShortcodesAction::class,
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\ListShortcodesAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => Action\EditShortcodeTagsAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/tags',
            'middleware' => Action\EditShortcodeTagsAction::class,
            'allowed_methods' => ['PUT'],
        ],

        // Visits
        [
            'name' => Action\GetVisitsAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/visits',
            'middleware' => Action\GetVisitsAction::class,
            'allowed_methods' => ['GET'],
        ],

        // Tags
        [
            'name' => Action\ListTagsAction::class,
            'path' => '/rest/v{version:1}/tags',
            'middleware' => Action\ListTagsAction::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
