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

            Service\IpApiLocationResolver::class => ConfigAbstractFactory::class,
            Service\GeoLite2LocationResolver::class => ConfigAbstractFactory::class,
            Service\PreviewGenerator::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleClient::class,
            'translator' => Translator::class,
            'logger' => LoggerInterface::class,
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
            Service\IpLocationResolverInterface::class => Service\GeoLite2LocationResolver::class,
        ],
        'abstract_factories' => [
            Factory\DottedAccessConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Reader::class => ['config.geolite2.db_location'],

        Template\Extension\TranslatorExtension::class => ['translator'],
        Middleware\LocaleMiddleware::class => ['translator'],
        Service\IpApiLocationResolver::class => ['httpClient'],
        Service\GeoLite2LocationResolver::class => [Reader::class],
        Service\PreviewGenerator::class => [
            Image\ImageBuilder::class,
            Filesystem::class,
            'config.preview_generation.files_location',
        ],
    ],

];
