<?php
declare(strict_types=1);

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Factory;
use Shlinkio\Shlink\Common\Image;
use Shlinkio\Shlink\Common\Image\ImageBuilder;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Common\Service;
use Shlinkio\Shlink\Common\Template\Extension\TranslatorExtension;
use Symfony\Component\Filesystem\Filesystem;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            EntityManager::class => Factory\EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Cache::class => Factory\CacheFactory::class,
            'Logger_Shlink' => Factory\LoggerFactory::class,
            Filesystem::class => InvokableFactory::class,

            Translator::class => Factory\TranslatorFactory::class,
            TranslatorExtension::class => ConfigAbstractFactory::class,
            LocaleMiddleware::class => ConfigAbstractFactory::class,

            Image\ImageBuilder::class => Image\ImageBuilderFactory::class,

            Service\IpApiLocationResolver::class => ConfigAbstractFactory::class,
            Service\PreviewGenerator::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            'translator' => Translator::class,
            'logger' => LoggerInterface::class,
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
        ],
        'abstract_factories' => [
            Factory\DottedAccessConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        TranslatorExtension::class => ['translator'],
        LocaleMiddleware::class => ['translator'],
        Service\IpApiLocationResolver::class => ['httpClient'],
        Service\PreviewGenerator::class => [
            ImageBuilder::class,
            Filesystem::class,
            'config.preview_generation.files_location',
        ],
    ],

];
