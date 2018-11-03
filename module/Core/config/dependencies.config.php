<?php
declare(strict_types=1);

use Doctrine\Common\Cache\Cache;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Middleware;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Response\NotFoundHandler;
use Shlinkio\Shlink\Core\Service;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'dependencies' => [
        'factories' => [
            Options\AppOptions::class => ConfigAbstractFactory::class,
            Options\DeleteShortUrlsOptions::class => ConfigAbstractFactory::class,
            Options\NotFoundShortUrlOptions::class => ConfigAbstractFactory::class,
            NotFoundHandler::class => ConfigAbstractFactory::class,

            // Services
            Service\UrlShortener::class => ConfigAbstractFactory::class,
            Service\VisitsTracker::class => ConfigAbstractFactory::class,
            Service\ShortUrlService::class => ConfigAbstractFactory::class,
            Service\VisitService::class => ConfigAbstractFactory::class,
            Service\Tag\TagService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,

            // Middleware
            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,
            Action\PreviewAction::class => ConfigAbstractFactory::class,
            Middleware\QrCodeCacheMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        NotFoundHandler::class => [TemplateRendererInterface::class],

        Options\AppOptions::class => ['config.app_options'],
        Options\DeleteShortUrlsOptions::class => ['config.delete_short_urls'],
        Options\NotFoundShortUrlOptions::class => ['config.url_shortener.not_found_short_url'],

        // Services
        Service\UrlShortener::class => [
            'httpClient',
            'em',
            'config.url_shortener.validate_url',
            'config.url_shortener.shortcode_chars',
        ],
        Service\VisitsTracker::class => ['em'],
        Service\ShortUrlService::class => ['em'],
        Service\VisitService::class => ['em'],
        Service\Tag\TagService::class => ['em'],
        Service\ShortUrl\DeleteShortUrlService::class => ['em', Options\DeleteShortUrlsOptions::class],

        // Middleware
        Action\RedirectAction::class => [
            Service\UrlShortener::class,
            Service\VisitsTracker::class,
            Options\AppOptions::class,
            'Logger_Shlink',
        ],
        Action\PixelAction::class => [
            Service\UrlShortener::class,
            Service\VisitsTracker::class,
            Options\AppOptions::class,
            'Logger_Shlink',
        ],
        Action\QrCodeAction::class => [RouterInterface::class, Service\UrlShortener::class, 'Logger_Shlink'],
        Action\PreviewAction::class => [PreviewGenerator::class, Service\UrlShortener::class, 'Logger_Shlink'],
        Middleware\QrCodeCacheMiddleware::class => [Cache::class],
    ],

];
