<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Fig\Http\Message\RequestMethodInterface;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action as CoreAction;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\ConfigProvider;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Middleware\Mercure\NotConfiguredMercureErrorHandler;

// The order of the routes defined here matters. Changing it might cause path conflicts
return (static function (): array {
    $contentNegotiationMiddleware = Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class;
    $dropDomainMiddleware = Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class;
    $overrideDomainMiddleware = Middleware\ShortUrl\OverrideDomainMiddleware::class;

    return [

        'routes' => [
            // Rest
            ...ConfigProvider::applyRoutesPrefix([
                Action\HealthAction::getRouteDef(),

                // Visits
                Action\Visit\ShortUrlVisitsAction::getRouteDef([$dropDomainMiddleware]),
                Action\Visit\TagVisitsAction::getRouteDef(),
                Action\Visit\DomainVisitsAction::getRouteDef(),
                Action\Visit\GlobalVisitsAction::getRouteDef(),
                Action\Visit\OrphanVisitsAction::getRouteDef(),
                Action\Visit\NonOrphanVisitsAction::getRouteDef(),

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

                // Tags
                Action\Tag\ListTagsAction::getRouteDef(),
                Action\Tag\TagsStatsAction::getRouteDef(),
                Action\Tag\DeleteTagsAction::getRouteDef(),
                Action\Tag\UpdateTagAction::getRouteDef(),

                // Domains
                Action\Domain\ListDomainsAction::getRouteDef(),
                Action\Domain\DomainRedirectsAction::getRouteDef(),

                Action\MercureInfoAction::getRouteDef([NotConfiguredMercureErrorHandler::class]),
            ]),

            // Non-rest
            [
                'name' => CoreAction\RobotsAction::class,
                'path' => '/robots.txt',
                'middleware' => [
                    CoreAction\RobotsAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name' => CoreAction\PixelAction::class,
                'path' => '/{shortCode:.+}/track',
                'middleware' => [
                    IpAddress::class,
                    CoreAction\PixelAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name' => CoreAction\QrCodeAction::class,
                'path' => '/{shortCode:.+}/qr-code',
                'middleware' => [
                    CoreAction\QrCodeAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name' => CoreAction\RedirectAction::class,
                'path' => '/{shortCode:.+}',
                'middleware' => [
                    IpAddress::class,
                    CoreAction\RedirectAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
        ],

    ];
})();
