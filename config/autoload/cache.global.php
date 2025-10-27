<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return (static function (): array {
    $redisServers = EnvVars::REDIS_SERVERS->loadFromEnv();
    $redis = ['pub_sub_enabled' => $redisServers !== null && EnvVars::REDIS_PUB_SUB_ENABLED->loadFromEnv()];
    $cacheRedisBlock = $redisServers === null ? [] : [
        'redis' => [
            'servers' => $redisServers,
            'sentinel_service' => EnvVars::REDIS_SENTINEL_SERVICE->loadFromEnv(),
            'username' => EnvVars::REDIS_SERVERS_USER->loadFromEnv(),
            'password' => EnvVars::REDIS_SERVERS_PASSWORD->loadFromEnv(),
        ],
    ];

    return [
        'cache' => [
            'namespace' => EnvVars::CACHE_NAMESPACE->loadFromEnv(),
            ...$cacheRedisBlock,
        ],
        'redis' => $redis,
    ];
})();
