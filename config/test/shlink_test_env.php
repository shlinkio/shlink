<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [
    EnvVars::APP_ENV->value => 'test',

    // URL shortener
    EnvVars::DEFAULT_DOMAIN->value => 's.test',
    EnvVars::IS_HTTPS_ENABLED->value => false,

    // Disable title auto-resolution, as it slows down API and CLI tests
    EnvVars::AUTO_RESOLVE_TITLES->value => false,
];
