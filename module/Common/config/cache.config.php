<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Doctrine\Common\Cache as DoctrineCache;

return [

    'dependencies' => [
        'factories' => [
            DoctrineCache\Cache::class => Cache\CacheFactory::class,
            Cache\RedisFactory::SERVICE_NAME => Cache\RedisFactory::class,
        ],
    ],

];
