<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use GeoIp2\Database\Reader;
use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RKA\Middleware\IpAddress;
use Symfony\Component\Filesystem\Filesystem;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

return [

    'dependencies' => [
        'factories' => [
            EntityManager::class => Factory\EntityManagerFactory::class,
            GuzzleClient::class => InvokableFactory::class,
            Cache::class => Factory\CacheFactory::class,
            'Logger_Shlink' => Factory\LoggerFactory::class,
            Filesystem::class => InvokableFactory::class,
            Reader::class => ConfigAbstractFactory::class,

            Translator::class => Factory\TranslatorFactory::class,
            Template\Extension\TranslatorExtension::class => ConfigAbstractFactory::class,

            Middleware\LocaleMiddleware::class => ConfigAbstractFactory::class,
            IpAddress::class => Middleware\IpAddressMiddlewareFactory::class,

            Image\ImageBuilder::class => Image\ImageBuilderFactory::class,

            IpGeolocation\IpApiLocationResolver::class => ConfigAbstractFactory::class,
            IpGeolocation\GeoLite2LocationResolver::class => ConfigAbstractFactory::class,
            IpGeolocation\ChainIpLocationResolver::class => ConfigAbstractFactory::class,
            IpGeolocation\GeoLite2\GeoLite2Options::class => ConfigAbstractFactory::class,
            IpGeolocation\GeoLite2\DbUpdater::class => ConfigAbstractFactory::class,

            Service\PreviewGenerator::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleClient::class,
            'translator' => Translator::class,

            'logger' => LoggerInterface::class,
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',

            IpGeolocation\IpLocationResolverInterface::class => IpGeolocation\ChainIpLocationResolver::class,
        ],
        'abstract_factories' => [
            Factory\DottedAccessConfigAbstractFactory::class,
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

        Template\Extension\TranslatorExtension::class => ['translator'],
        Middleware\LocaleMiddleware::class => ['translator'],

        IpGeolocation\IpApiLocationResolver::class => ['httpClient'],
        IpGeolocation\GeoLite2LocationResolver::class => [Reader::class],
        IpGeolocation\ChainIpLocationResolver::class => [
            IpGeolocation\GeoLite2LocationResolver::class,
            IpGeolocation\IpApiLocationResolver::class,
        ],
        IpGeolocation\GeoLite2\GeoLite2Options::class => ['config.geolite2'],
        IpGeolocation\GeoLite2\DbUpdater::class => [
            GuzzleClient::class,
            Filesystem::class,
            IpGeolocation\GeoLite2\GeoLite2Options::class,
        ],

        Service\PreviewGenerator::class => [
            Image\ImageBuilder::class,
            Filesystem::class,
            'config.preview_generation.files_location',
        ],
    ],

];
