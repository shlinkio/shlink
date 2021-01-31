<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\ErrorHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;

return [

    'dependencies' => [
        'factories' => [
            ErrorHandler\NotFoundRedirectHandler::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTemplateHandler::class => InvokableFactory::class,

            Options\AppOptions::class => ConfigAbstractFactory::class,
            Options\DeleteShortUrlsOptions::class => ConfigAbstractFactory::class,
            Options\NotFoundRedirectOptions::class => ConfigAbstractFactory::class,
            Options\UrlShortenerOptions::class => ConfigAbstractFactory::class,

            Service\UrlShortener::class => ConfigAbstractFactory::class,
            Service\VisitsTracker::class => ConfigAbstractFactory::class,
            Service\ShortUrlService::class => ConfigAbstractFactory::class,
            Visit\VisitLocator::class => ConfigAbstractFactory::class,
            Visit\VisitsStatsHelper::class => ConfigAbstractFactory::class,
            Tag\TagService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,
            Service\ShortUrl\ShortUrlResolver::class => ConfigAbstractFactory::class,
            Service\ShortUrl\ShortCodeHelper::class => ConfigAbstractFactory::class,
            Domain\DomainService::class => ConfigAbstractFactory::class,

            Util\UrlValidator::class => ConfigAbstractFactory::class,
            Util\DoctrineBatchHelper::class => ConfigAbstractFactory::class,
            Util\RedirectResponseHelper::class => ConfigAbstractFactory::class,

            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,

            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => ConfigAbstractFactory::class,

            Mercure\MercureUpdatesGenerator::class => ConfigAbstractFactory::class,

            Importer\ImportedLinksProcessor::class => ConfigAbstractFactory::class,
        ],

        'aliases' => [
            ImportedLinksProcessorInterface::class => Importer\ImportedLinksProcessor::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ErrorHandler\NotFoundRedirectHandler::class => [
            NotFoundRedirectOptions::class,
            Util\RedirectResponseHelper::class,
            'config.router.base_path',
        ],

        Options\AppOptions::class => ['config.app_options'],
        Options\DeleteShortUrlsOptions::class => ['config.delete_short_urls'],
        Options\NotFoundRedirectOptions::class => ['config.not_found_redirects'],
        Options\UrlShortenerOptions::class => ['config.url_shortener'],

        Service\UrlShortener::class => [
            Util\UrlValidator::class,
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            Service\ShortUrl\ShortCodeHelper::class,
        ],
        Service\VisitsTracker::class => [
            'em',
            EventDispatcherInterface::class,
            'config.url_shortener.anonymize_remote_addr',
        ],
        Service\ShortUrlService::class => [
            'em',
            Service\ShortUrl\ShortUrlResolver::class,
            Util\UrlValidator::class,
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
        ],
        Visit\VisitLocator::class => ['em'],
        Visit\VisitsStatsHelper::class => ['em'],
        Tag\TagService::class => ['em'],
        Service\ShortUrl\DeleteShortUrlService::class => [
            'em',
            Options\DeleteShortUrlsOptions::class,
            Service\ShortUrl\ShortUrlResolver::class,
        ],
        Service\ShortUrl\ShortUrlResolver::class => ['em'],
        Service\ShortUrl\ShortCodeHelper::class => ['em'],
        Domain\DomainService::class => ['em', 'config.url_shortener.domain.hostname'],

        Util\UrlValidator::class => ['httpClient', Options\UrlShortenerOptions::class],
        Util\DoctrineBatchHelper::class => ['em'],
        Util\RedirectResponseHelper::class => [Options\UrlShortenerOptions::class],

        Action\RedirectAction::class => [
            Service\ShortUrl\ShortUrlResolver::class,
            Service\VisitsTracker::class,
            Options\AppOptions::class,
            Util\RedirectResponseHelper::class,
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

        ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => ['em'],

        Mercure\MercureUpdatesGenerator::class => ['config.url_shortener.domain'],

        Importer\ImportedLinksProcessor::class => [
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            Service\ShortUrl\ShortCodeHelper::class,
            Util\DoctrineBatchHelper::class,
        ],
    ],

];
