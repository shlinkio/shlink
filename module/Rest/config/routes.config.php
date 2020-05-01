<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

$contentNegotiationMiddleware = Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class;
$dropDomainMiddleware = Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class;

return [

    'routes' => [
        Action\HealthAction::getRouteDef(),

        // Short codes
        Action\ShortUrl\CreateShortUrlAction::getRouteDef([
            $contentNegotiationMiddleware,
            $dropDomainMiddleware,
            Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class,
        ]),
        Action\ShortUrl\SingleStepCreateShortUrlAction::getRouteDef([$contentNegotiationMiddleware]),
        Action\ShortUrl\EditShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\DeleteShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\ResolveShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\ListShortUrlsAction::getRouteDef(),
        Action\ShortUrl\EditShortUrlTagsAction::getRouteDef([$dropDomainMiddleware]),

        // Visits
        Action\Visit\ShortUrlVisitsAction::getRouteDef([$dropDomainMiddleware]),
        Action\Visit\GlobalVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\CreateTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),

        Action\MercureInfoAction::getRouteDef(),
    ],

];
