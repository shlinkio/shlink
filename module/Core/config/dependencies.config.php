<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\Doctrine\EntityRepositoryFactory;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdater;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Symfony\Component\Lock;

use const Shlinkio\Shlink\LOCAL_LOCK_FACTORY;

return [

    'dependencies' => [
        'factories' => [
            ErrorHandler\NotFoundTypeResolverMiddleware::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTrackerMiddleware::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundRedirectHandler::class => ConfigAbstractFactory::class,
            ErrorHandler\NotFoundTemplateHandler::class => InvokableFactory::class,

            Config\Options\AppOptions::class => [Config\Options\AppOptions::class, 'fromEnv'],
            Config\Options\DeleteShortUrlsOptions::class => [Config\Options\DeleteShortUrlsOptions::class, 'fromEnv'],
            Config\Options\NotFoundRedirectOptions::class => [Config\Options\NotFoundRedirectOptions::class, 'fromEnv'],
            Config\Options\RedirectOptions::class => [Config\Options\RedirectOptions::class, 'fromEnv'],
            Config\Options\UrlShortenerOptions::class => [Config\Options\UrlShortenerOptions::class, 'fromEnv'],
            Config\Options\TrackingOptions::class => [Config\Options\TrackingOptions::class, 'fromEnv'],
            Config\Options\QrCodeOptions::class => [Config\Options\QrCodeOptions::class, 'fromEnv'],
            Config\Options\RabbitMqOptions::class => [Config\Options\RabbitMqOptions::class, 'fromEnv'],
            Config\Options\RobotsOptions::class => [Config\Options\RobotsOptions::class, 'fromEnv'],
            Config\Options\RealTimeUpdatesOptions::class => [Config\Options\RealTimeUpdatesOptions::class, 'fromEnv'],
            Config\Options\CorsOptions::class => [Config\Options\CorsOptions::class, 'fromEnv'],

            RedirectRule\ShortUrlRedirectRuleService::class => ConfigAbstractFactory::class,
            RedirectRule\ShortUrlRedirectionResolver::class => ConfigAbstractFactory::class,

            ShortUrl\UrlShortener::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlService::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlListService::class => ConfigAbstractFactory::class,
            ShortUrl\DeleteShortUrlService::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlResolver::class => ConfigAbstractFactory::class,
            ShortUrl\ShortUrlVisitsDeleter::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class => ConfigAbstractFactory::class,
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlStringifier::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class => ConfigAbstractFactory::class,
            ShortUrl\Helper\ShortUrlRedirectionBuilder::class => ConfigAbstractFactory::class,
            ShortUrl\Transformer\ShortUrlDataTransformer::class => ConfigAbstractFactory::class,
            ShortUrl\Middleware\ExtraPathRedirectMiddleware::class => ConfigAbstractFactory::class,
            ShortUrl\Middleware\TrimTrailingSlashMiddleware::class => ConfigAbstractFactory::class,
            ShortUrl\Repository\ShortUrlRepository::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],
            ShortUrl\Repository\ShortUrlListRepository::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],
            ShortUrl\Repository\CrawlableShortCodesQuery::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],
            ShortUrl\Repository\ExpiredShortUrlsRepository::class => [
                EntityRepositoryFactory::class,
                ShortUrl\Entity\ShortUrl::class,
            ],

            Tag\TagService::class => ConfigAbstractFactory::class,
            Tag\Repository\TagRepository::class => [EntityRepositoryFactory::class, Tag\Entity\Tag::class],

            Domain\DomainService::class => ConfigAbstractFactory::class,
            Domain\Repository\DomainRepository::class => [EntityRepositoryFactory::class, Domain\Entity\Domain::class],

            Visit\VisitsTracker::class => ConfigAbstractFactory::class,
            Visit\RequestTracker::class => ConfigAbstractFactory::class,
            Visit\VisitsDeleter::class => ConfigAbstractFactory::class,
            Visit\Geolocation\VisitLocator::class => ConfigAbstractFactory::class,
            Visit\Geolocation\VisitToLocationHelper::class => ConfigAbstractFactory::class,
            Visit\VisitsStatsHelper::class => ConfigAbstractFactory::class,
            Visit\Repository\VisitIterationRepository::class => [
                EntityRepositoryFactory::class,
                Visit\Entity\Visit::class,
            ],
            Visit\Repository\VisitDeleterRepository::class => [
                EntityRepositoryFactory::class,
                Visit\Entity\Visit::class,
            ],
            Visit\Listener\ShortUrlVisitsCountTracker::class => InvokableFactory::class,
            Visit\Listener\OrphanVisitsCountTracker::class => InvokableFactory::class,

            Util\DoctrineBatchHelper::class => ConfigAbstractFactory::class,
            Util\RedirectResponseHelper::class => ConfigAbstractFactory::class,

            Config\NotFoundRedirectResolver::class => ConfigAbstractFactory::class,

            Action\RedirectAction::class => ConfigAbstractFactory::class,
            Action\PixelAction::class => ConfigAbstractFactory::class,
            Action\QrCodeAction::class => ConfigAbstractFactory::class,
            Action\RobotsAction::class => ConfigAbstractFactory::class,

            EventDispatcher\PublishingUpdatesGenerator::class => ConfigAbstractFactory::class,

            Geolocation\GeolocationDbUpdater::class => ConfigAbstractFactory::class,
            Geolocation\Middleware\IpGeolocationMiddleware::class => ConfigAbstractFactory::class,

            Importer\ImportedLinksProcessor::class => ConfigAbstractFactory::class,

            Crawling\CrawlingHelper::class => ConfigAbstractFactory::class,

            Matomo\MatomoOptions::class => [Matomo\MatomoOptions::class, 'fromEnv'],
            Matomo\MatomoTrackerBuilder::class => ConfigAbstractFactory::class,
            Matomo\MatomoVisitSender::class => ConfigAbstractFactory::class,
        ],

        'aliases' => [
            ImportedLinksProcessorInterface::class => Importer\ImportedLinksProcessor::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Matomo\MatomoTrackerBuilder::class => [Matomo\MatomoOptions::class],
        Matomo\MatomoVisitSender::class => [
            Matomo\MatomoTrackerBuilder::class,
            ShortUrlStringifier::class,
            Visit\Repository\VisitIterationRepository::class,
        ],

        ErrorHandler\NotFoundTypeResolverMiddleware::class => ['config.router.base_path'],
        ErrorHandler\NotFoundTrackerMiddleware::class => [Visit\RequestTracker::class],
        ErrorHandler\NotFoundRedirectHandler::class => [
            NotFoundRedirectOptions::class,
            Config\NotFoundRedirectResolver::class,
            Domain\DomainService::class,
        ],

        ShortUrl\UrlShortener::class => [
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class,
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class,
            EventDispatcherInterface::class,
            ShortUrl\Repository\ShortUrlRepository::class,
        ],
        Visit\VisitsTracker::class => [
            'em',
            EventDispatcherInterface::class,
            Config\Options\TrackingOptions::class,
        ],
        Visit\RequestTracker::class => [Visit\VisitsTracker::class, Config\Options\TrackingOptions::class],
        Visit\VisitsDeleter::class => [Visit\Repository\VisitDeleterRepository::class],
        ShortUrl\ShortUrlService::class => [
            'em',
            ShortUrl\ShortUrlResolver::class,
            ShortUrl\Helper\ShortUrlTitleResolutionHelper::class,
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
        ],
        ShortUrl\ShortUrlListService::class => [
            ShortUrl\Repository\ShortUrlListRepository::class,
            Config\Options\UrlShortenerOptions::class,
        ],
        Visit\Geolocation\VisitLocator::class => ['em', Visit\Repository\VisitIterationRepository::class],
        Visit\Geolocation\VisitToLocationHelper::class => [IpLocationResolverInterface::class],
        Visit\VisitsStatsHelper::class => ['em', Config\Options\UrlShortenerOptions::class],
        Tag\TagService::class => ['em', Tag\Repository\TagRepository::class],
        ShortUrl\DeleteShortUrlService::class => [
            'em',
            Config\Options\DeleteShortUrlsOptions::class,
            ShortUrl\ShortUrlResolver::class,
            ShortUrl\Repository\ExpiredShortUrlsRepository::class,
        ],
        ShortUrl\ShortUrlResolver::class => [
            ShortUrl\Repository\ShortUrlRepository::class,
            Config\Options\UrlShortenerOptions::class,
        ],
        ShortUrl\ShortUrlVisitsDeleter::class => [
            Visit\Repository\VisitDeleterRepository::class,
            ShortUrl\ShortUrlResolver::class,
        ],
        ShortUrl\Helper\ShortCodeUniquenessHelper::class => [
            ShortUrl\Repository\ShortUrlRepository::class,
            Config\Options\UrlShortenerOptions::class,
        ],
        Domain\DomainService::class => [
            'em',
            Config\Options\UrlShortenerOptions::class,
            Domain\Repository\DomainRepository::class,
        ],

        Util\DoctrineBatchHelper::class => ['em'],
        Util\RedirectResponseHelper::class => [Config\Options\RedirectOptions::class],

        Config\NotFoundRedirectResolver::class => [Util\RedirectResponseHelper::class, 'Logger_Shlink'],

        RedirectRule\ShortUrlRedirectRuleService::class => ['em'],
        RedirectRule\ShortUrlRedirectionResolver::class => [RedirectRule\ShortUrlRedirectRuleService::class],

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
            Config\Options\QrCodeOptions::class,
        ],
        Action\RobotsAction::class => [Crawling\CrawlingHelper::class, Config\Options\RobotsOptions::class],

        ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class => [
            'em',
            Config\Options\UrlShortenerOptions::class,
            Lock\LockFactory::class,
        ],
        ShortUrl\Helper\ShortUrlStringifier::class => [
            Config\Options\UrlShortenerOptions::class,
            'config.router.base_path',
        ],
        ShortUrl\Helper\ShortUrlTitleResolutionHelper::class => [
            'httpClient',
            Config\Options\UrlShortenerOptions::class,
            'Logger_Shlink',
        ],
        ShortUrl\Helper\ShortUrlRedirectionBuilder::class => [
            Config\Options\TrackingOptions::class,
            RedirectRule\ShortUrlRedirectionResolver::class,
        ],
        ShortUrl\Transformer\ShortUrlDataTransformer::class => [ShortUrl\Helper\ShortUrlStringifier::class],
        ShortUrl\Middleware\ExtraPathRedirectMiddleware::class => [
            ShortUrl\ShortUrlResolver::class,
            Visit\RequestTracker::class,
            ShortUrl\Helper\ShortUrlRedirectionBuilder::class,
            Util\RedirectResponseHelper::class,
            Config\Options\UrlShortenerOptions::class,
        ],
        ShortUrl\Middleware\TrimTrailingSlashMiddleware::class => [Config\Options\UrlShortenerOptions::class],

        EventDispatcher\PublishingUpdatesGenerator::class => [ShortUrl\Transformer\ShortUrlDataTransformer::class],

        GeolocationDbUpdater::class => [
            DbUpdater::class,
            LOCAL_LOCK_FACTORY,
            Config\Options\TrackingOptions::class,
            'em',
        ],
        Geolocation\Middleware\IpGeolocationMiddleware::class => [
            IpLocationResolverInterface::class,
            DbUpdater::class,
            'Logger_Shlink',
            Config\Options\TrackingOptions::class,
        ],

        Importer\ImportedLinksProcessor::class => [
            'em',
            ShortUrl\Resolver\PersistenceShortUrlRelationResolver::class,
            ShortUrl\Helper\ShortCodeUniquenessHelper::class,
            Util\DoctrineBatchHelper::class,
            RedirectRule\ShortUrlRedirectRuleService::class,
        ],

        Crawling\CrawlingHelper::class => [ShortUrl\Repository\CrawlableShortCodesQuery::class],
    ],

];
