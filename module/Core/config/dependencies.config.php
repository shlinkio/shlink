<?php
declare(strict_types=1);

use Doctrine\Common\Cache\Cache;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Middleware;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service;
use Zend\Expressive\Router\RouterInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'dependencies' => [
        'factories' => [
            Options\AppOptions::class => Options\AppOptionsFactory::class,

            // Services
            Service\UrlShortener::class => ConfigAbstractFactory::class,
            Service\VisitsTracker::class => ConfigAbstractFactory::class,
            Service\ShortUrlService::class => ConfigAbstractFactory::class,
            Service\VisitService::class => ConfigAbstractFactory::class,
            Service\Tag\TagService::class => ConfigAbstractFactory::class,

            // Middleware
            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,
            Action\PreviewAction::class => ConfigAbstractFactory::class,
            Middleware\QrCodeCacheMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        // Services
        Service\UrlShortener::class => ['httpClient', 'em', Cache::class, 'config.url_shortener.shortcode_chars'],
        Service\VisitsTracker::class => ['em'],
        Service\ShortUrlService::class => ['em'],
        Service\VisitService::class => ['em'],
        Service\Tag\TagService::class => ['em'],

        // Middleware
        Action\RedirectAction::class => [Service\UrlShortener::class, Service\VisitsTracker::class, 'Logger_Shlink'],
        Action\QrCodeAction::class => [RouterInterface::class, Service\UrlShortener::class, 'Logger_Shlink'],
        Action\PreviewAction::class => [PreviewGenerator::class, Service\UrlShortener::class],
        Middleware\QrCodeCacheMiddleware::class => [Cache::class],
    ],

];
