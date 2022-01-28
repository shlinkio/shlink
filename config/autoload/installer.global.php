<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI;

use Shlinkio\Shlink\Installer\Config\Option;
use Shlinkio\Shlink\Installer\Util\InstallationCommand;

return [

    'installer' => [
        'enabled_options' => [
            Option\Database\DatabaseDriverConfigOption::class,
            Option\Database\DatabaseNameConfigOption::class,
            Option\Database\DatabaseHostConfigOption::class,
            Option\Database\DatabasePortConfigOption::class,
            Option\Database\DatabaseUserConfigOption::class,
            Option\Database\DatabasePasswordConfigOption::class,
            Option\Database\DatabaseUnixSocketConfigOption::class,
            Option\UrlShortener\ShortDomainHostConfigOption::class,
            Option\UrlShortener\ShortDomainSchemaConfigOption::class,
            Option\Visit\VisitsWebhooksConfigOption::class,
            Option\Visit\OrphanVisitsWebhooksConfigOption::class,
            Option\Redirect\BaseUrlRedirectConfigOption::class,
            Option\Redirect\InvalidShortUrlRedirectConfigOption::class,
            Option\Redirect\Regular404RedirectConfigOption::class,
            Option\Visit\VisitsThresholdConfigOption::class,
            Option\BasePathConfigOption::class,
            Option\Worker\TaskWorkerNumConfigOption::class,
            Option\Worker\WebWorkerNumConfigOption::class,
            Option\Redis\RedisServersConfigOption::class,
            Option\Redis\RedisSentinelServiceConfigOption::class,
            Option\UrlShortener\ShortCodeLengthOption::class,
            Option\Mercure\EnableMercureConfigOption::class,
            Option\Mercure\MercurePublicUrlConfigOption::class,
            Option\Mercure\MercureInternalUrlConfigOption::class,
            Option\Mercure\MercureJwtSecretConfigOption::class,
            Option\UrlShortener\GeoLiteLicenseKeyConfigOption::class,
            Option\UrlShortener\RedirectStatusCodeConfigOption::class,
            Option\UrlShortener\RedirectCacheLifeTimeConfigOption::class,
            Option\UrlShortener\AutoResolveTitlesConfigOption::class,
            Option\UrlShortener\AppendExtraPathConfigOption::class,
            Option\Tracking\IpAnonymizationConfigOption::class,
            Option\Tracking\OrphanVisitsTrackingConfigOption::class,
            Option\Tracking\DisableTrackParamConfigOption::class,
            Option\Tracking\DisableTrackingFromConfigOption::class,
            Option\Tracking\DisableTrackingConfigOption::class,
            Option\Tracking\DisableIpTrackingConfigOption::class,
            Option\Tracking\DisableReferrerTrackingConfigOption::class,
            Option\Tracking\DisableUaTrackingConfigOption::class,
            Option\QrCode\DefaultSizeConfigOption::class,
            Option\QrCode\DefaultMarginConfigOption::class,
            Option\QrCode\DefaultFormatConfigOption::class,
            Option\QrCode\DefaultErrorCorrectionConfigOption::class,
            Option\QrCode\DefaultRoundBlockSizeConfigOption::class,
            Option\RabbitMq\RabbitMqEnabledConfigOption::class,
            Option\RabbitMq\RabbitMqHostConfigOption::class,
            Option\RabbitMq\RabbitMqPortConfigOption::class,
            Option\RabbitMq\RabbitMqUserConfigOption::class,
            Option\RabbitMq\RabbitMqPasswordConfigOption::class,
            Option\RabbitMq\RabbitMqVhostConfigOption::class,
        ],

        'installation_commands' => [
            InstallationCommand::DB_CREATE_SCHEMA => [
                'command' => 'bin/cli ' . Command\Db\CreateDatabaseCommand::NAME,
            ],
            InstallationCommand::DB_MIGRATE => [
                'command' => 'bin/cli ' . Command\Db\MigrateDatabaseCommand::NAME,
            ],
            InstallationCommand::GEOLITE_DOWNLOAD_DB => [
                'command' => 'bin/cli ' . Command\Visit\DownloadGeoLiteDbCommand::NAME,
            ],
        ],
    ],

];
