<?php
declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        [
            'name' => Action\AuthenticateAction::class,
            'path' => '/rest/v{version:1}/authenticate',
            'middleware' => Action\AuthenticateAction::class,
            'allowed_methods' => [RequestMethod::METHOD_POST],
        ],

        // Short codes
        [
            'name' => Action\CreateShortcodeAction::class,
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\CreateShortcodeAction::class,
            'allowed_methods' => [RequestMethod::METHOD_POST],
        ],
        [
            'name' => Action\ResolveUrlAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}',
            'middleware' => Action\ResolveUrlAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => Action\ListShortcodesAction::class,
            'path' => '/rest/v{version:1}/short-codes',
            'middleware' => Action\ListShortcodesAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => Action\EditShortcodeTagsAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/tags',
            'middleware' => Action\EditShortcodeTagsAction::class,
            'allowed_methods' => [RequestMethod::METHOD_PUT],
        ],

        // Visits
        [
            'name' => Action\GetVisitsAction::class,
            'path' => '/rest/v{version:1}/short-codes/{shortCode}/visits',
            'middleware' => Action\GetVisitsAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],

        // Tags
        [
            'name' => Action\Tag\ListTagsAction::class,
            'path' => '/rest/v{version:1}/tags',
            'middleware' => Action\Tag\ListTagsAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => Action\Tag\DeleteTagsAction::class,
            'path' => '/rest/v{version:1}/tags',
            'middleware' => Action\Tag\DeleteTagsAction::class,
            'allowed_methods' => [RequestMethod::METHOD_DELETE],
        ],
        [
            'name' => Action\Tag\CreateTagsAction::class,
            'path' => '/rest/v{version:1}/tags',
            'middleware' => Action\Tag\CreateTagsAction::class,
            'allowed_methods' => [RequestMethod::METHOD_POST],
        ],
        [
            'name' => Action\Tag\UpdateTagAction::class,
            'path' => '/rest/v{version:1}/tags',
            'middleware' => Action\Tag\UpdateTagAction::class,
            'allowed_methods' => [RequestMethod::METHOD_PUT],
        ],
    ],

];
