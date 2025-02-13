<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ServerRequestInterface;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action as CoreAction;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Geolocation\Middleware\IpGeolocationMiddleware;
use Shlinkio\Shlink\Core\ShortUrl\Middleware\TrimTrailingSlashMiddleware;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\ConfigProvider;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Middleware\Mercure\NotConfiguredMercureErrorHandler;

use function Shlinkio\Shlink\Core\ipAddressFromRequest;
use function sprintf;

return (static function (): array {
    $dropDomainMiddleware = Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class;
    $overrideDomainMiddleware = Middleware\ShortUrl\OverrideDomainMiddleware::class;
    $shortUrlRouteSuffix = EnvVars::SHORT_URL_TRAILING_SLASH->loadFromEnv() ? '[/]' : '';

    return [

        // The order of the routes defined here matters. Changing it might cause path conflicts
        'routes' => [
            // Rest
            ...ConfigProvider::applyRoutesPrefix([
                Action\HealthAction::getRouteDef([
                    IpAddress::class,
                    \Laminas\Stratigility\middleware(
                        function (ServerRequestInterface $req) {
                            $addr = ipAddressFromRequest($req);
                            $right = $addr === '172.20.16.1' ? 'right' : 'wrong';
                            $remoteAddr = $req->getServerParams()['REMOTE_ADDR'];
                            return new TextResponse(<<<RESP
                                    Resolved IP: {$addr} ({$right})
                                    
                                    REMOTE_ADDR: {$remoteAddr}
                                    X-Real-IP: {$req->getHeaderLine('X-Real-Ip')}
                                    X-Forwarded-For: {$req->getHeaderLine('X-Forwarded-For')}
                                RESP
                            );
                        },
                    ),
                ]),

                // Visits and rules routes must go first, as they have a more specific path, otherwise, when
                // multi-segment slugs are enabled, routes with a less-specific path might match first

                // Visits.
                Action\Visit\ShortUrlVisitsAction::getRouteDef([$dropDomainMiddleware]),
                Action\ShortUrl\DeleteShortUrlVisitsAction::getRouteDef([$dropDomainMiddleware]),
                Action\Visit\TagVisitsAction::getRouteDef(),
                Action\Visit\DomainVisitsAction::getRouteDef(),
                Action\Visit\GlobalVisitsAction::getRouteDef(),
                Action\Visit\OrphanVisitsAction::getRouteDef(),
                Action\Visit\DeleteOrphanVisitsAction::getRouteDef(),
                Action\Visit\NonOrphanVisitsAction::getRouteDef(),

                //Redirect rules
                Action\RedirectRule\ListRedirectRulesAction::getRouteDef([$dropDomainMiddleware]),
                Action\RedirectRule\SetRedirectRulesAction::getRouteDef([$dropDomainMiddleware]),

                // Short URLs
                Action\ShortUrl\CreateShortUrlAction::getRouteDef([
                    $dropDomainMiddleware,
                    $overrideDomainMiddleware,
                    Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class,
                ]),
                Action\ShortUrl\SingleStepCreateShortUrlAction::getRouteDef([
                    Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class,
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
                'path' => '/{shortCode}/track',
                'middleware' => [
                    IpAddress::class,
                    IpGeolocationMiddleware::class,
                    CoreAction\PixelAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name' => CoreAction\QrCodeAction::class,
                'path' => '/{shortCode}/qr-code',
                'middleware' => [
                    CoreAction\QrCodeAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name' => CoreAction\RedirectAction::class,
                'path' => sprintf('/{shortCode}%s', $shortUrlRouteSuffix),
                'middleware' => [
                    IpAddress::class,
                    IpGeolocationMiddleware::class,
                    TrimTrailingSlashMiddleware::class,
                    CoreAction\RedirectAction::class,
                ],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
        ],

    ];
})();
