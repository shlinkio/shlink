<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI;

use GeoIp2\Database\Reader;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Shlinkio\Shlink\Common\Doctrine\NoDbNameConnectionFactory;
use Shlinkio\Shlink\Core\Domain\DomainService;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\Tag\TagService;
use Shlinkio\Shlink\Core\Visit;
use Shlinkio\Shlink\Installer\Factory\ProcessHelperFactory;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console as SymfonyCli;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\PhpExecutableFinder;

use const Shlinkio\Shlink\LOCAL_LOCK_FACTORY;

return [

    'dependencies' => [
        'factories' => [
            SymfonyCli\Application::class => Factory\ApplicationFactory::class,
            SymfonyCli\Helper\ProcessHelper::class => ProcessHelperFactory::class,
            PhpExecutableFinder::class => InvokableFactory::class,

            GeoLite\GeolocationDbUpdater::class => ConfigAbstractFactory::class,
            Util\ProcessRunner::class => ConfigAbstractFactory::class,

            ApiKey\RoleResolver::class => ConfigAbstractFactory::class,

            Command\ShortUrl\CreateShortUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ResolveUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ListShortUrlsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\GetShortUrlVisitsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\DeleteShortUrlCommand::class => ConfigAbstractFactory::class,

            Command\Visit\DownloadGeoLiteDbCommand::class => ConfigAbstractFactory::class,
            Command\Visit\LocateVisitsCommand::class => ConfigAbstractFactory::class,
            Command\Visit\GetOrphanVisitsCommand::class => ConfigAbstractFactory::class,
            Command\Visit\GetNonOrphanVisitsCommand::class => ConfigAbstractFactory::class,

            Command\Api\GenerateKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\DisableKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\ListKeysCommand::class => ConfigAbstractFactory::class,

            Command\Tag\ListTagsCommand::class => ConfigAbstractFactory::class,
            Command\Tag\RenameTagCommand::class => ConfigAbstractFactory::class,
            Command\Tag\DeleteTagsCommand::class => ConfigAbstractFactory::class,
            Command\Tag\GetTagVisitsCommand::class => ConfigAbstractFactory::class,

            Command\Db\CreateDatabaseCommand::class => ConfigAbstractFactory::class,
            Command\Db\MigrateDatabaseCommand::class => ConfigAbstractFactory::class,

            Command\Domain\ListDomainsCommand::class => ConfigAbstractFactory::class,
            Command\Domain\DomainRedirectsCommand::class => ConfigAbstractFactory::class,
            Command\Domain\GetDomainVisitsCommand::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        GeoLite\GeolocationDbUpdater::class => [
            DbUpdater::class,
            Reader::class,
            LOCAL_LOCK_FACTORY,
            TrackingOptions::class,
        ],
        Util\ProcessRunner::class => [SymfonyCli\Helper\ProcessHelper::class],
        ApiKey\RoleResolver::class => [DomainService::class, 'config.url_shortener.domain.hostname'],

        Command\ShortUrl\CreateShortUrlCommand::class => [
            ShortUrl\UrlShortener::class,
            ShortUrlStringifier::class,
            UrlShortenerOptions::class,
        ],
        Command\ShortUrl\ResolveUrlCommand::class => [ShortUrl\ShortUrlResolver::class],
        Command\ShortUrl\ListShortUrlsCommand::class => [
            ShortUrl\ShortUrlListService::class,
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
        ],
        Command\ShortUrl\GetShortUrlVisitsCommand::class => [Visit\VisitsStatsHelper::class],
        Command\ShortUrl\DeleteShortUrlCommand::class => [ShortUrl\DeleteShortUrlService::class],

        Command\Visit\DownloadGeoLiteDbCommand::class => [GeoLite\GeolocationDbUpdater::class],
        Command\Visit\LocateVisitsCommand::class => [
            Visit\Geolocation\VisitLocator::class,
            Visit\Geolocation\VisitToLocationHelper::class,
            LockFactory::class,
        ],
        Command\Visit\GetOrphanVisitsCommand::class => [Visit\VisitsStatsHelper::class],
        Command\Visit\GetNonOrphanVisitsCommand::class => [Visit\VisitsStatsHelper::class, ShortUrlStringifier::class],

        Command\Api\GenerateKeyCommand::class => [ApiKeyService::class, ApiKey\RoleResolver::class],
        Command\Api\DisableKeyCommand::class => [ApiKeyService::class],
        Command\Api\ListKeysCommand::class => [ApiKeyService::class],

        Command\Tag\ListTagsCommand::class => [TagService::class],
        Command\Tag\RenameTagCommand::class => [TagService::class],
        Command\Tag\DeleteTagsCommand::class => [TagService::class],
        Command\Tag\GetTagVisitsCommand::class => [Visit\VisitsStatsHelper::class, ShortUrlStringifier::class],

        Command\Domain\ListDomainsCommand::class => [DomainService::class],
        Command\Domain\DomainRedirectsCommand::class => [DomainService::class],
        Command\Domain\GetDomainVisitsCommand::class => [Visit\VisitsStatsHelper::class, ShortUrlStringifier::class],

        Command\Db\CreateDatabaseCommand::class => [
            LockFactory::class,
            Util\ProcessRunner::class,
            PhpExecutableFinder::class,
            'em',
            NoDbNameConnectionFactory::SERVICE_NAME,
        ],
        Command\Db\MigrateDatabaseCommand::class => [
            LockFactory::class,
            Util\ProcessRunner::class,
            PhpExecutableFinder::class,
        ],
    ],

];
