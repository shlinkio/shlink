<?php
declare(strict_types=1);

use Symfony\Component\Lock;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'locks' => [
        'locks_dir' => __DIR__ . '/../../data/locks',
    ],

    'dependencies' => [
        'factories' => [
            Lock\Store\FlockStore::class => ConfigAbstractFactory::class,
            Lock\Factory::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'lock_adapter' => Lock\Store\FlockStore::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Lock\Store\FlockStore::class => ['config.locks.locks_dir'],
        Lock\Factory::class => ['lock_adapter'],
    ],

];
