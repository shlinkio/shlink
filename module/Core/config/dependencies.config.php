<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Mezzio\Template\TemplateRendererInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Domain\Resolver;
use Shlinkio\Shlink\Core\ErrorHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;

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
            Visit\VisitLocator::class => ConfigAbstractFactory::class,
            Visit\VisitsStatsHelper::class => ConfigAbstractFactory::class,
            Service\Tag\TagService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\ShortUrlResolver::class => ConfigAbstractFactory::class,

            Util\UrlValidator::class => ConfigAbstractFactory::class,

            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,

            Resolver\PersistenceDomainResolver::class => ConfigAbstractFactory::class,

            Mercure\MercureUpdatesGenerator::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ErrorHandler\NotFoundRedirectHandler::class => [NotFoundRedirectOptions::class, 'config.router.base_path'],
        ErrorHandler\NotFoundTemplateHandler::class => [TemplateRendererInterface::class],

        Options\AppOptions::class => ['config.app_options'],
        Options\DeleteShortUrlsOptions::class => ['config.delete_short_urls'],
        Options\NotFoundRedirectOptions::class => ['config.not_found_redirects'],
        Options\UrlShortenerOptions::class => ['config.url_shortener'],

        Service\UrlShortener::class => [Util\UrlValidator::class, 'em', Resolver\PersistenceDomainResolver::class],
        Service\VisitsTracker::class => ['em', EventDispatcherInterface::class],
        Service\ShortUrlService::class => ['em', Service\ShortUrl\ShortUrlResolver::class, Util\UrlValidator::class],
        Visit\VisitLocator::class => ['em'],
        Visit\VisitsStatsHelper::class => ['em'],
        Service\Tag\TagService::class => ['em'],
        Service\ShortUrl\DeleteShortUrlService::class => [
            'em',
            Options\DeleteShortUrlsOptions::class,
            Service\ShortUrl\ShortUrlResolver::class,
        ],
        Service\ShortUrl\ShortUrlResolver::class => ['em'],

        Util\UrlValidator::class => ['httpClient', Options\UrlShortenerOptions::class],

        Action\RedirectAction::class => [
            Service\ShortUrl\ShortUrlResolver::class,
            Service\VisitsTracker::class,
            Options\AppOptions::class,
            'Logger_Shlink',
        ],
        Action\PixelAction::class => [
            Service\ShortUrl\ShortUrlResolver::class,
            Service\VisitsTracker::class,
            Options\AppOptions::class,
            'Logger_Shlink',
        ],
        Action\QrCodeAction::class => [
            Service\ShortUrl\ShortUrlResolver::class,
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],

        Resolver\PersistenceDomainResolver::class => ['em'],

        Mercure\MercureUpdatesGenerator::class => ['config.url_shortener.domain'],
    ],

];
