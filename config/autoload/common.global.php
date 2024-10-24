<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Shlinkio\Shlink\Core\Config\EnvVars;

return (function () {
    $isDev = EnvVars::isDevEnv();

    return [

        'debug' => $isDev,

        // Disabling config cache for cli, ensures it's never used for RoadRunner, and also that console
        // commands don't generate a cache file that's then used by php-fpm web executions
        ConfigAggregator::ENABLE_CACHE => ! $isDev && PHP_SAPI !== 'cli',

    ];
})();
