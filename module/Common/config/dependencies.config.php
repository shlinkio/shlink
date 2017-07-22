<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Factory;
use Shlinkio\Shlink\Common\Image;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Common\Service;
use Shlinkio\Shlink\Common\Twig\Extension\TranslatorExtension;
use Symfony\Component\Filesystem\Filesystem;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'invokables' => [
            Filesystem::class => Filesystem::class,
        ],
        'factories' => [
            EntityManager::class => Factory\EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Cache::class => Factory\CacheFactory::class,
            'Logger_Shlink' => Factory\LoggerFactory::class,

            Translator::class => Factory\TranslatorFactory::class,
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
        'abstract_factories' => [
            Factory\DottedAccessConfigAbstractFactory::class,
        ],
    ],

];
