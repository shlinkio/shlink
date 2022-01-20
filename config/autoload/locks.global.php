<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Predis\ClientInterface as PredisClient;
use Shlinkio\Shlink\Common\Logger\LoggerAwareDelegatorFactory;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Symfony\Component\Lock;

use const Shlinkio\Shlink\LOCAL_LOCK_FACTORY;

return [

    'locks' => [
        'locks_dir' => __DIR__ . '/../../data/locks',
    ],

    'dependencies' => [
        'factories' => [
            Lock\Store\FlockStore::class => ConfigAbstractFactory::class,
            Lock\Store\RedisStore::class => ConfigAbstractFactory::class,
            Lock\LockFactory::class => ConfigAbstractFactory::class,
            LOCAL_LOCK_FACTORY => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'lock_store' => EnvVars::REDIS_SERVERS()->existsInEnv() ? 'redis_lock_store' : 'local_lock_store',

            'redis_lock_store' => Lock\Store\RedisStore::class,
            'local_lock_store' => Lock\Store\FlockStore::class,
        ],
        'delegators' => [
            Lock\LockFactory::class => [
                LoggerAwareDelegatorFactory::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Lock\Store\FlockStore::class => ['config.locks.locks_dir'],
        Lock\Store\RedisStore::class => [PredisClient::class],
        Lock\LockFactory::class => ['lock_store'],
        LOCAL_LOCK_FACTORY => ['local_lock_store'],
    ],

];
