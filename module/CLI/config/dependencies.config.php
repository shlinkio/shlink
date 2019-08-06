<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI;

use Doctrine\DBAL\Connection;
use GeoIp2\Database\Reader;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\Common\Doctrine\NoDbNameConnectionFactory;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Service;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console as SymfonyCli;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Process\PhpExecutableFinder;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            SymfonyCli\Application::class => Factory\ApplicationFactory::class,
            SymfonyCli\Helper\ProcessHelper::class => Factory\ProcessHelperFactory::class,
            PhpExecutableFinder::class => InvokableFactory::class,

            GeolocationDbUpdater::class => ConfigAbstractFactory::class,

            Command\ShortUrl\GenerateShortUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ResolveUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ListShortUrlsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\GetVisitsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\GeneratePreviewCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\DeleteShortUrlCommand::class => ConfigAbstractFactory::class,

            Command\Visit\LocateVisitsCommand::class => ConfigAbstractFactory::class,
            Command\Visit\UpdateDbCommand::class => ConfigAbstractFactory::class,

            Command\Config\GenerateCharsetCommand::class => InvokableFactory::class,
            Command\Config\GenerateSecretCommand::class => InvokableFactory::class,

            Command\Api\GenerateKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\DisableKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\ListKeysCommand::class => ConfigAbstractFactory::class,

            Command\Tag\ListTagsCommand::class => ConfigAbstractFactory::class,
            Command\Tag\CreateTagCommand::class => ConfigAbstractFactory::class,
            Command\Tag\RenameTagCommand::class => ConfigAbstractFactory::class,
            Command\Tag\DeleteTagsCommand::class => ConfigAbstractFactory::class,

            Command\Db\CreateDatabaseCommand::class => ConfigAbstractFactory::class,
            Command\Db\MigrateDatabaseCommand::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        GeolocationDbUpdater::class => [DbUpdater::class, Reader::class, Locker::class],

        Command\ShortUrl\GenerateShortUrlCommand::class => [Service\UrlShortener::class, 'config.url_shortener.domain'],
        Command\ShortUrl\ResolveUrlCommand::class => [Service\UrlShortener::class],
        Command\ShortUrl\ListShortUrlsCommand::class => [Service\ShortUrlService::class, 'config.url_shortener.domain'],
        Command\ShortUrl\GetVisitsCommand::class => [Service\VisitsTracker::class],
        Command\ShortUrl\GeneratePreviewCommand::class => [Service\ShortUrlService::class, PreviewGenerator::class],
        Command\ShortUrl\DeleteShortUrlCommand::class => [Service\ShortUrl\DeleteShortUrlService::class],

        Command\Visit\LocateVisitsCommand::class => [
            Service\VisitService::class,
            IpLocationResolverInterface::class,
            Locker::class,
            GeolocationDbUpdater::class,
        ],
        Command\Visit\UpdateDbCommand::class => [DbUpdater::class],

        Command\Api\GenerateKeyCommand::class => [ApiKeyService::class],
        Command\Api\DisableKeyCommand::class => [ApiKeyService::class],
        Command\Api\ListKeysCommand::class => [ApiKeyService::class],

        Command\Tag\ListTagsCommand::class => [Service\Tag\TagService::class],
        Command\Tag\CreateTagCommand::class => [Service\Tag\TagService::class],
        Command\Tag\RenameTagCommand::class => [Service\Tag\TagService::class],
        Command\Tag\DeleteTagsCommand::class => [Service\Tag\TagService::class],

        Command\Db\CreateDatabaseCommand::class => [
            Locker::class,
            SymfonyCli\Helper\ProcessHelper::class,
            PhpExecutableFinder::class,
            Connection::class,
            NoDbNameConnectionFactory::SERVICE_NAME,
        ],
        Command\Db\MigrateDatabaseCommand::class => [
            Locker::class,
            SymfonyCli\Helper\ProcessHelper::class,
            PhpExecutableFinder::class,
        ],
    ],

];
