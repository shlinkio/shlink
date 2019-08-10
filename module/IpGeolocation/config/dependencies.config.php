<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation;

use GeoIp2\Database\Reader;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\Filesystem\Filesystem;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            IpApiLocationResolver::class => ConfigAbstractFactory::class,
            GeoLite2LocationResolver::class => ConfigAbstractFactory::class,
            EmptyIpLocationResolver::class => InvokableFactory::class,
            ChainIpLocationResolver::class => ConfigAbstractFactory::class,
            GeoLite2\GeoLite2Options::class => ConfigAbstractFactory::class,
            GeoLite2\DbUpdater::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            IpLocationResolverInterface::class => ChainIpLocationResolver::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        IpApiLocationResolver::class => ['httpClient'],
        GeoLite2LocationResolver::class => [Reader::class],
        ChainIpLocationResolver::class => [
            GeoLite2LocationResolver::class,
            IpApiLocationResolver::class,
            EmptyIpLocationResolver::class,
        ],
        GeoLite2\GeoLite2Options::class => ['config.geolite2'],
        GeoLite2\DbUpdater::class => [
            GuzzleClient::class,
            Filesystem::class,
            GeoLite2\GeoLite2Options::class,
        ],
    ],

];
