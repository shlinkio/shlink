<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'matomo' => [
        'enabled' => (bool) EnvVars::MATOMO_ENABLED->loadFromEnv(false),
        'base_url' => EnvVars::MATOMO_BASE_URL->loadFromEnv(),
        'site_id' => EnvVars::MATOMO_SITE_ID->loadFromEnv(),
        'api_token' => EnvVars::MATOMO_API_TOKEN->loadFromEnv(),
    ],

];
