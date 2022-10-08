<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action as CoreAction;
use Shlinkio\Shlink\Core\ShortUrl\Middleware\TrimTrailingSlashMiddleware;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Middleware\Mercure\NotConfiguredMercureErrorHandler;

use function sprintf;

$registerRestRoutes = static function (Application $app): void {
    $contentNegotiationMiddleware = Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class;
    $dropDomainMiddleware = Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class;
    $overrideDomainMiddleware = Middleware\ShortUrl\OverrideDomainMiddleware::class;

    Action\HealthAction::registerUnversioned($app);
    Action\HealthAction::register($app);

    // Visits
    Action\Visit\ShortUrlVisitsAction::register($app, [$dropDomainMiddleware]);
    Action\Visit\TagVisitsAction::register($app);
    Action\Visit\DomainVisitsAction::register($app);
    Action\Visit\GlobalVisitsAction::register($app);
    Action\Visit\OrphanVisitsAction::register($app);
    Action\Visit\NonOrphanVisitsAction::register($app);

    // Short URLs
    Action\ShortUrl\CreateShortUrlAction::register($app, [
        $contentNegotiationMiddleware,
        $dropDomainMiddleware,
        $overrideDomainMiddleware,
        Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class,
    ]);
    Action\ShortUrl\SingleStepCreateShortUrlAction::register($app, [
        $contentNegotiationMiddleware,
        $overrideDomainMiddleware,
    ]);
    Action\ShortUrl\EditShortUrlAction::register($app, [$dropDomainMiddleware]);
    Action\ShortUrl\DeleteShortUrlAction::register($app, [$dropDomainMiddleware]);
    Action\ShortUrl\ResolveShortUrlAction::register($app, [$dropDomainMiddleware]);
    Action\ShortUrl\ListShortUrlsAction::register($app);

    // Tags
    Action\Tag\ListTagsAction::register($app);
    Action\Tag\TagsStatsAction::register($app);
    Action\Tag\DeleteTagsAction::register($app);
    Action\Tag\UpdateTagAction::register($app);

    // Domains
    Action\Domain\ListDomainsAction::register($app);
    Action\Domain\DomainRedirectsAction::register($app);

    Action\MercureInfoAction::register($app, [NotConfiguredMercureErrorHandler::class]);
};

$registerRegularRoutes = static function (Application $app, ContainerInterface $container): void {
    $config = $container->get('config');
    $trailingSlashEnabled = (bool) ($config['url_shortener']['trailing_slash_enabled'] ?? false);

    $app->get('/robots.txt', [CoreAction\RobotsAction::class], CoreAction\RobotsAction::class);
    $app->get('/{shortCode}/track', [IpAddress::class, CoreAction\PixelAction::class], CoreAction\PixelAction::class);
    $app->get('/{shortCode}/qr-code', [CoreAction\QrCodeAction::class], CoreAction\QrCodeAction::class);
    $app->get(sprintf('/{shortCode}%s', $trailingSlashEnabled ? '[/]' : ''), [
        IpAddress::class,
        TrimTrailingSlashMiddleware::class,
        CoreAction\RedirectAction::class,
    ], CoreAction\RedirectAction::class);
};

return static function (
    Application $app,
    MiddlewareFactory $middlewareFactory,
    ContainerInterface $container,
) use (
    $registerRestRoutes,
    $registerRegularRoutes,
): void {
    $registerRestRoutes($app);
    $registerRegularRoutes($app, $container);
};
