<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

return [

    'debug' => false,

    // Disabling config cache for cli, ensures it's never used for openswoole/RoadRunner, and also that console
    // commands don't generate a cache file that's then used by php-fpm web executions
    ConfigAggregator::ENABLE_CACHE => PHP_SAPI !== 'cli',

];
