<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Shlinkio\Shlink\Rest\Action;

return [

    'routes' => [
        Action\AuthenticateAction::getRouteDef(),

        // Short codes
        Action\ShortUrl\CreateShortUrlAction::getRouteDef([
            Middleware\ShortCode\CreateShortCodeContentNegotiationMiddleware::class,
        ]),
        Action\ShortUrl\SingleStepCreateShortUrlAction::getRouteDef([
            Middleware\ShortCode\CreateShortCodeContentNegotiationMiddleware::class,
        ]),
        Action\ShortUrl\EditShortUrlAction::getRouteDef(),
        Action\ShortUrl\DeleteShortUrlAction::getRouteDef(),
        Action\ShortUrl\ResolveShortUrlAction::getRouteDef(),
        Action\ShortUrl\ListShortUrlsAction::getRouteDef(),
        Action\ShortUrl\EditShortUrlTagsAction::getRouteDef(),

        // Visits
        Action\Visit\GetVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\CreateTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),
    ],

];
