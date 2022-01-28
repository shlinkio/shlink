<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

return [

    'not_found_redirects' => [
        'invalid_short_url' => EnvVars::DEFAULT_INVALID_SHORT_URL_REDIRECT()->loadFromEnv(),
        'regular_404' => EnvVars::DEFAULT_REGULAR_404_REDIRECT()->loadFromEnv(),
        'base_url' => EnvVars::DEFAULT_BASE_URL_REDIRECT()->loadFromEnv(),
    ],

    'redirects' => [
        'redirect_status_code' => (int) EnvVars::REDIRECT_STATUS_CODE()->loadFromEnv(DEFAULT_REDIRECT_STATUS_CODE),
        'redirect_cache_lifetime' => (int) EnvVars::REDIRECT_CACHE_LIFETIME()->loadFromEnv(
            DEFAULT_REDIRECT_CACHE_LIFETIME,
        ),
    ],

];
