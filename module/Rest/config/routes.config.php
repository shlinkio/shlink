<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

return [

    'routes' => [
        Action\HealthAction::getRouteDef(),

        // Short codes
        Action\ShortUrl\CreateShortUrlAction::getRouteDef([
            Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class,
        ]),
        Action\ShortUrl\SingleStepCreateShortUrlAction::getRouteDef([
            Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class,
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
