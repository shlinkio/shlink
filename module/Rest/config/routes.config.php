<?php
declare(strict_types=1);

use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        Action\AuthenticateAction::getRouteDef(),

        // Short codes
        Action\ShortCode\CreateShortCodeAction::getRouteDef(),
        Action\ShortCode\SingleStepCreateShortCodeAction::getRouteDef(),
        Action\ShortCode\EditShortCodeAction::getRouteDef(),
        Action\ShortCode\ResolveUrlAction::getRouteDef(),
        Action\ShortCode\ListShortCodesAction::getRouteDef(),
        Action\ShortCode\EditShortCodeTagsAction::getRouteDef(),

        // Visits
        Action\GetVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\CreateTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),
    ],

];
