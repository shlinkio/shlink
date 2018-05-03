<?php
declare(strict_types=1);

use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        Action\AuthenticateAction::getRouteDef(),

        // Short codes
        Action\CreateShortCodeAction::getRouteDef(),
//        [
//            'name' => Action\CreateShortCodeAction::class,
//            'path' => '/short-codes',
//            'middleware' => Action\CreateShortCodeAction::class,
//            'allowed_methods' => [RequestMethod::METHOD_GET],
//        ],
        Action\EditShortCodeAction::getRouteDef(),
        Action\ResolveUrlAction::getRouteDef(),
        Action\ListShortCodesAction::getRouteDef(),
        Action\EditShortCodeTagsAction::getRouteDef(),

        // Visits
        Action\GetVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\CreateTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),
    ],

];
