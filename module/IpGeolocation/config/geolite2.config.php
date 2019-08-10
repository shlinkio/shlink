<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation;

use GeoIp2\Database\Reader;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

return [

    'dependencies' => [
        'factories' => [
            Reader::class => ConfigAbstractFactory::class,
        ],
        'delegators' => [
            // The GeoLite2 db reader has to be lazy so that it does not try to load the DB file at app bootstrapping.
            // By doing so, it would fail the first time shlink tries to download it.
            Reader::class => [
                LazyServiceFactory::class,
            ],
        ],

        'lazy_services' => [
            'class_map' => [
                Reader::class => Reader::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Reader::class => ['config.geolite2.db_location'],
    ],

];
