<?php
declare(strict_types=1);

use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        Action\AuthenticateAction::getRouteDef(),

        // Short codes
        Action\ShortCode\CreateShortCodeAction::getRouteDef(),
        Action\ShortCode\SingleStepCreateShortCodeAction::getRouteDef(),
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
