<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    EnvVars::APP_ENV->value => 'test',

    // URL shortener
    EnvVars::DEFAULT_DOMAIN->value => 's.test',
    EnvVars::IS_HTTPS_ENABLED->value => false,

];
