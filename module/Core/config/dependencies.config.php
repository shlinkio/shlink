<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\Doctrine\EntityRepositoryFactory;
use Shlinkio\Shlink\Config\Factory\ValinorConfigFactory;
use Shlinkio\Shlink\Core\ErrorHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

return [

    'dependencies' => [
        'factories' => [
            ErrorHandler\NotFoundTypeResolverMiddleware::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTrackerMiddleware::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundRedirectHandler::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTemplateHandler::class => InvokableFactory::class,

            Options\AppOptions::class => [ValinorConfigFactory::class, 'config.app_options'],
            Options\DeleteShortUrlsOptions::class => [ValinorConfigFactory::class, 'config.delete_short_urls'],
            Options\NotFoundRedirectOptions::class => [ValinorConfigFactory::class, 'config.not_found_redirects'],
            Options\RedirectOptions::class => [ValinorConfigFactory::class, 'config.redirects'],
            Options\UrlShortenerOptions::class => [ValinorConfigFactory::class, 'config.url_shortener'],
            Options\TrackingOptions::class => [ValinorConfigFactory::class, 'config.tracking'],
            Options\QrCodeOptions::class => [ValinorConfigFactory::class, 'config.qr_codes'],
            Options\RabbitMqOptions::class => [ValinorConfigFactory::class, 'config.rabbitmq'],
            Options\WebhookOptions::class => ConfigAbstractFactory::class,

            ShortUrl\UrlShortener::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlService::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlListService::class => ConfigAbstractFactory::class,
            ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlResolver::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class => ConfigAbstractFactory::class,
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlStringifier::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlRedirectionBuilder::class => ConfigAbstractFactory::class,
            ShortUrl\Transformer\ShortUrlDataTransformer::class => ConfigAbstractFactory::class,
            ShortUrl\Middleware\ExtraPathRedirectMiddleware::class => ConfigAbstractFactory::class,
            ShortUrl\Middleware\TrimTrailingSlashMiddleware::class => ConfigAbstractFactory::class,
            ShortUrl\Repository\ShortUrlListRepository::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],
            ShortUrl\Repository\CrawlableShortCodesQuery::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],

            Tag\TagService::class => ConfigAbstractFactory::class,

            Domain\DomainService::class => ConfigAbstractFactory::class,

            Visit\VisitsTracker::class => ConfigAbstractFactory::class,
            Visit\RequestTracker::class => ConfigAbstractFactory::class,
            Visit\Geolocation\VisitLocator::class => ConfigAbstractFactory::class,
            Visit\Geolocation\VisitToLocationHelper::class => ConfigAbstractFactory::class,
            Visit\VisitsStatsHelper::class => ConfigAbstractFactory::class,
            Visit\Transformer\OrphanVisitDataTransformer::class => InvokableFactory::class,
            Visit\Repository\VisitLocationRepository::class => [
                EntityRepositoryFactory::class,
                Visit\Entity\Visit::class,
            ],

            Util\UrlValidator::class => ConfigAbstractFactory::class,
            Util\DoctrineBatchHelper::class => ConfigAbstractFactory::class,
            Util\RedirectResponseHelper::class => ConfigAbstractFactory::class,

            Config\NotFoundRedirectResolver::class => ConfigAbstractFactory::class,

            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,
            Action\RobotsAction::class => ConfigAbstractFactory::class,

            EventDispatcher\PublishingUpdatesGenerator::class => ConfigAbstractFactory::class,

            Importer\ImportedLinksProcessor::class => ConfigAbstractFactory::class,

            Crawling\CrawlingHelper::class => ConfigAbstractFactory::class,
        ],

        'aliases' => [
            ImportedLinksProcessorInterface::class => Importer\ImportedLinksProcessor::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ErrorHandler\NotFoundTypeResolverMiddleware::class => ['config.router.base_path'],
        ErrorHandler\NotFoundTrackerMiddleware::class => [Visit\RequestTracker::class],
        ErrorHandler\NotFoundRedirectHandler::class => [
            NotFoundRedirectOptions::class,
            Config\NotFoundRedirectResolver::class,
            Domain\DomainService::class,
        ],

        Options\WebhookOptions::class => ['config.visits_webhooks'],

        ShortUrl\UrlShortener::class => [
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class,
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class,
            EventDispatcherInterface::class,
        ],
        Visit\VisitsTracker::class => [
            'em',
            EventDispatcherInterface::class,
            Options\TrackingOptions::class,
        ],
        Visit\RequestTracker::class => [Visit\VisitsTracker::class, Options\TrackingOptions::class],
        ShortUrl\ShortUrlService::class => [
            'em',
            ShortUrl\ShortUrlResolver::class,
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class,
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
        ],
        ShortUrl\ShortUrlListService::class => [
            ShortUrl\Repository\ShortUrlListRepository::class,
            Options\UrlShortenerOptions::class,
        ],
        Visit\Geolocation\VisitLocator::class => ['em', Visit\Repository\VisitLocationRepository::class],
        Visit\Geolocation\VisitToLocationHelper::class => [IpLocationResolverInterface::class],
        Visit\VisitsStatsHelper::class => ['em'],
        Tag\TagService::class => ['em'],
        ShortUrl\DeleteShortUrlService::class => [
            'em',
            Options\DeleteShortUrlsOptions::class,
            ShortUrl\ShortUrlResolver::class,
        ],
        ShortUrl\ShortUrlResolver::class => ['em', Options\UrlShortenerOptions::class],
        ShortUrl\Helper\ShortCodeUniquenessHelper::class => ['em', Options\UrlShortenerOptions::class],
        Domain\DomainService::class => ['em', 'config.url_shortener.domain.hostname'],

        Util\UrlValidator::class => ['httpClient', Options\UrlShortenerOptions::class],
        Util\DoctrineBatchHelper::class => ['em'],
        Util\RedirectResponseHelper::class => [Options\RedirectOptions::class],

        Config\NotFoundRedirectResolver::class => [Util\RedirectResponseHelper::class, 'Logger_Shlink'],

        Action\RedirectAction::class => [
            ShortUrl\ShortUrlResolver::class,
            Visit\RequestTracker::class,
            ShortUrl\Helper\ShortUrlRedirectionBuilder::class,
            Util\RedirectResponseHelper::class,
        ],
        Action\PixelAction::class => [ShortUrl\ShortUrlResolver::class, Visit\RequestTracker::class],
        Action\QrCodeAction::class => [
            ShortUrl\ShortUrlResolver::class,
            ShortUrl\Helper\ShortUrlStringifier::class,
            'Logger_Shlink',
            Options\QrCodeOptions::class,
        ],
        Action\RobotsAction::class => [Crawling\CrawlingHelper::class],

        ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => ['em'],
        ShortUrl\Helper\ShortUrlStringifier::class => ['config.url_shortener.domain', 'config.router.base_path'],
        ShortUrl\Helper\ShortUrlTitleResolutionHelper::class => [Util\UrlValidator::class],
        ShortUrl\Helper\ShortUrlRedirectionBuilder::class => [Options\TrackingOptions::class],
        ShortUrl\Transformer\ShortUrlDataTransformer::class => [ShortUrl\Helper\ShortUrlStringifier::class],
        ShortUrl\Middleware\ExtraPathRedirectMiddleware::class => [
            ShortUrl\ShortUrlResolver::class,
            Visit\RequestTracker::class,
            ShortUrl\Helper\ShortUrlRedirectionBuilder::class,
            Util\RedirectResponseHelper::class,
            Options\UrlShortenerOptions::class,
        ],
        ShortUrl\Middleware\TrimTrailingSlashMiddleware::class => [Options\UrlShortenerOptions::class],

        EventDispatcher\PublishingUpdatesGenerator::class => [
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
            Visit\Transformer\OrphanVisitDataTransformer::class,
        ],

        Importer\ImportedLinksProcessor::class => [
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class,
            Util\DoctrineBatchHelper::class,
        ],

        Crawling\CrawlingHelper::class => [ShortUrl\Repository\CrawlableShortCodesQuery::class],
    ],

];
