<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'app_options' => [
        'name' => 'Shlink',
        'version' => EnvVars::isDevEnv() ? 'latest' : '%SHLINK_VERSION%',
    ],

];
