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
            Resolver\IpApiLocationResolver::class => ConfigAbstractFactory::class,
            Resolver\GeoLite2LocationResolver::class => ConfigAbstractFactory::class,
            Resolver\EmptyIpLocationResolver::class => InvokableFactory::class,
            Resolver\ChainIpLocationResolver::class => ConfigAbstractFactory::class,

            GeoLite2\GeoLite2Options::class => ConfigAbstractFactory::class,
            GeoLite2\DbUpdater::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            Resolver\IpLocationResolverInterface::class => Resolver\ChainIpLocationResolver::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Resolver\IpApiLocationResolver::class => [GuzzleClient::class],
        Resolver\GeoLite2LocationResolver::class => [Reader::class],
        Resolver\ChainIpLocationResolver::class => [
            Resolver\GeoLite2LocationResolver::class,
            Resolver\IpApiLocationResolver::class,
            Resolver\EmptyIpLocationResolver::class,
        ],

        GeoLite2\GeoLite2Options::class => ['config.geolite2'],
        GeoLite2\DbUpdater::class => [
            GuzzleClient::class,
            Filesystem::class,
            GeoLite2\GeoLite2Options::class,
        ],
    ],

];
