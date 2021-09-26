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
            Option\Database\DatabaseSqlitePathConfigOption::class,
            Option\Database\DatabaseMySqlOptionsConfigOption::class,
            Option\UrlShortener\ShortDomainHostConfigOption::class,
            Option\UrlShortener\ShortDomainSchemaConfigOption::class,
            Option\UrlShortener\ValidateUrlConfigOption::class,
            Option\Visit\VisitsWebhooksConfigOption::class,
            Option\Redirect\BaseUrlRedirectConfigOption::class,
            Option\Redirect\InvalidShortUrlRedirectConfigOption::class,
            Option\Redirect\Regular404RedirectConfigOption::class,
            Option\Visit\CheckVisitsThresholdConfigOption::class,
            Option\Visit\VisitsThresholdConfigOption::class,
            Option\BasePathConfigOption::class,
            Option\Worker\TaskWorkerNumConfigOption::class,
            Option\Worker\WebWorkerNumConfigOption::class,
            Option\RedisServersConfigOption::class,
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
            Option\Tracking\DisableTrackingConfigOption::class,
            Option\Tracking\DisableIpTrackingConfigOption::class,
            Option\Tracking\DisableReferrerTrackingConfigOption::class,
            Option\Tracking\DisableUaTrackingConfigOption::class,
            Option\QrCode\DefaultSizeConfigOption::class,
            Option\QrCode\DefaultMarginConfigOption::class,
            Option\QrCode\DefaultFormatConfigOption::class,
            Option\QrCode\DefaultErrorCorrectionConfigOption::class,
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
