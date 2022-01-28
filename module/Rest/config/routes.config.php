<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

$contentNegotiationMiddleware = Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class;
$dropDomainMiddleware = Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class;
$overrideDomainMiddleware = Middleware\ShortUrl\OverrideDomainMiddleware::class;

return [

    'routes' => [
        Action\HealthAction::getRouteDef(),

        // Short URLs
        Action\ShortUrl\CreateShortUrlAction::getRouteDef([
            $contentNegotiationMiddleware,
            $dropDomainMiddleware,
            $overrideDomainMiddleware,
            Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class,
        ]),
        Action\ShortUrl\SingleStepCreateShortUrlAction::getRouteDef([
            $contentNegotiationMiddleware,
            $overrideDomainMiddleware,
        ]),
        Action\ShortUrl\EditShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\DeleteShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\ResolveShortUrlAction::getRouteDef([$dropDomainMiddleware]),
        Action\ShortUrl\ListShortUrlsAction::getRouteDef(),

        // Visits
        Action\Visit\ShortUrlVisitsAction::getRouteDef([$dropDomainMiddleware]),
        Action\Visit\TagVisitsAction::getRouteDef(),
        Action\Visit\GlobalVisitsAction::getRouteDef(),
        Action\Visit\OrphanVisitsAction::getRouteDef(),
        Action\Visit\NonOrphanVisitsAction::getRouteDef(),

        // Tags
        Action\Tag\ListTagsAction::getRouteDef(),
        Action\Tag\TagsStatsAction::getRouteDef(),
        Action\Tag\DeleteTagsAction::getRouteDef(),
        Action\Tag\UpdateTagAction::getRouteDef(),

        // Domains
        Action\Domain\ListDomainsAction::getRouteDef(),
        Action\Domain\DomainRedirectsAction::getRouteDef(),

        Action\MercureInfoAction::getRouteDef(),
    ],

];
