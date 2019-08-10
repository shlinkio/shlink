<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

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
            GuzzleClient::class => InvokableFactory::class,
            Filesystem::class => InvokableFactory::class,
            Reader::class => ConfigAbstractFactory::class,

            Translator::class => Factory\TranslatorFactory::class,
            Template\Extension\TranslatorExtension::class => ConfigAbstractFactory::class,

            Middleware\LocaleMiddleware::class => ConfigAbstractFactory::class,
            Middleware\CloseDbConnectionMiddleware::class => ConfigAbstractFactory::class,
            IpAddress::class => Middleware\IpAddressMiddlewareFactory::class,

            Image\ImageBuilder::class => Image\ImageBuilderFactory::class,

            Service\PreviewGenerator::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'httpClient' => GuzzleClient::class,
            'translator' => Translator::class,

            'logger' => LoggerInterface::class,
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
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
        Middleware\CloseDbConnectionMiddleware::class => ['em'],

        Service\PreviewGenerator::class => [
            Image\ImageBuilder::class,
            Filesystem::class,
            'config.preview_generation.files_location',
        ],
    ],

];
