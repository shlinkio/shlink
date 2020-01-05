<?php

declare(strict_types=1);

use Shlinkio\Shlink\Installer\Config\Option;

return [

    'installer' => [
        'enabled_options' => [
            Option\DatabaseDriverConfigOption::class,
            Option\DatabaseNameConfigOption::class,
            Option\DatabaseHostConfigOption::class,
            Option\DatabasePortConfigOption::class,
            Option\DatabaseUserConfigOption::class,
            Option\DatabasePasswordConfigOption::class,
            Option\DatabaseSqlitePathConfigOption::class,
            Option\DatabaseMySqlOptionsConfigOption::class,
            Option\ShortDomainHostConfigOption::class,
            Option\ShortDomainSchemaConfigOption::class,
            Option\ValidateUrlConfigOption::class,
            Option\VisitsWebhooksConfigOption::class,
            Option\BaseUrlRedirectConfigOption::class,
            Option\InvalidShortUrlRedirectConfigOption::class,
            Option\Regular404RedirectConfigOption::class,
            Option\DisableTrackParamConfigOption::class,
            Option\CheckVisitsThresholdConfigOption::class,
            Option\VisitsThresholdConfigOption::class,
            Option\BasePathConfigOption::class,
            Option\TaskWorkerNumConfigOption::class,
            Option\WebWorkerNumConfigOption::class,
            Option\RedisServersConfigOption::class,
        ],

        'installation_commands' => [
            'db_create_schema' => [
                'command' => 'bin/cli db:create',
            ],
            'db_migrate' => [
                'command' => 'bin/cli db:migrate',
            ],
        ],
    ],

];
