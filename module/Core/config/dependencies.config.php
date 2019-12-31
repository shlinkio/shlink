<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\Common\Cache\Cache;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\ErrorHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'dependencies' => [
        'factories' => [
            ErrorHandler\NotFoundRedirectHandler::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTemplateHandler::class => ConfigAbstractFactory::class,

            Options\AppOptions::class => ConfigAbstractFactory::class,
            Options\DeleteShortUrlsOptions::class => ConfigAbstractFactory::class,
            Options\NotFoundRedirectOptions::class => ConfigAbstractFactory::class,
            Options\UrlShortenerOptions::class => ConfigAbstractFactory::class,

            Service\UrlShortener::class => ConfigAbstractFactory::class,
            Service\VisitsTracker::class => ConfigAbstractFactory::class,
            Service\ShortUrlService::class => ConfigAbstractFactory::class,
            Service\VisitService::class => ConfigAbstractFactory::class,
            Service\Tag\TagService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,

            Util\UrlValidator::class => ConfigAbstractFactory::class,

            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,

            Middleware\QrCodeCacheMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ErrorHandler\NotFoundRedirectHandler::class => [NotFoundRedirectOptions::class, 'config.router.base_path'],
        ErrorHandler\NotFoundTemplateHandler::class => [TemplateRendererInterface::class],

        Options\AppOptions::class => ['config.app_options'],
        Options\DeleteShortUrlsOptions::class => ['config.delete_short_urls'],
        Options\NotFoundRedirectOptions::class => ['config.not_found_redirects'],
        Options\UrlShortenerOptions::class => ['config.url_shortener'],

        Service\UrlShortener::class => [Util\UrlValidator::class, 'em', Options\UrlShortenerOptions::class],
        Service\VisitsTracker::class => ['em', EventDispatcherInterface::class],
        Service\ShortUrlService::class => ['em'],
        Service\VisitService::class => ['em'],
        Service\Tag\TagService::class => ['em'],
        Service\ShortUrl\DeleteShortUrlService::class => ['em', Options\DeleteShortUrlsOptions::class],

        Util\UrlValidator::class => ['httpClient'],

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

        Middleware\QrCodeCacheMiddleware::class => [Cache::class],
    ],

];
