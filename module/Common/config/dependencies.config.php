<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Factory\CacheFactory;
use Shlinkio\Shlink\Common\Factory\EntityManagerFactory;
use Shlinkio\Shlink\Common\Factory\LoggerFactory;
use Shlinkio\Shlink\Common\Factory\TranslatorFactory;
use Shlinkio\Shlink\Common\Image;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Common\Service;
use Shlinkio\Shlink\Common\Twig\Extension\TranslatorExtension;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            EntityManager::class => EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Cache::class => CacheFactory::class,
            'Logger_Shlink' => LoggerFactory::class,

            Translator::class => TranslatorFactory::class,
            TranslatorExtension::class => AnnotatedFactory::class,
            LocaleMiddleware::class => AnnotatedFactory::class,

            Image\ImageBuilder::class => Image\ImageBuilderFactory::class,

            Service\IpLocationResolver::class => AnnotatedFactory::class,
            Service\PreviewGenerator::class => AnnotatedFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            'translator' => Translator::class,
            'logger' => LoggerInterface::class,
            AnnotatedFactory::CACHE_SERVICE => Cache::class,
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
        ],
    ],

];
