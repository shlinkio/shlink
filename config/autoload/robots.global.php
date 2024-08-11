<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

return [

    'robots' => [
        'allow-all-short-urls' => (bool) Config\EnvVars::ROBOTS_ALLOW_ALL_SHORT_URLS->loadFromEnv(false),
        'user-agents' => splitByComma(Config\EnvVars::ROBOTS_USER_AGENTS->loadFromEnv()),
    ],

];
