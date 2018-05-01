<?php
declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        Action\AuthenticateAction::getRouteDef(),

        // Short codes
        [
            'name' => Action\CreateShortcodeAction::class,
            'path' => '/short-codes',
            'middleware' => Action\CreateShortcodeAction::class,
            'allowed_methods' => [RequestMethod::METHOD_POST],
        ],
//        [
//            'name' => Action\CreateShortcodeAction::class,
//            'path' => '/short-codes',
//            'middleware' => Action\CreateShortcodeAction::class,
//            'allowed_methods' => [RequestMethod::METHOD_GET],
//        ],
        Action\EditShortCodeAction::getRouteDef(),
        Action\ResolveUrlAction::getRouteDef(),
        Action\ListShortcodesAction::getRouteDef(),
        Action\EditShortcodeTagsAction::getRouteDef(),

        // Visits
        Action\GetVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\CreateTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),
    ],

];
