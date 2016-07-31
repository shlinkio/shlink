<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Shlinkio\Shlink\Common\ErrorHandler;
use Shlinkio\Shlink\Common\Factory\CacheFactory;
use Shlinkio\Shlink\Common\Factory\EntityManagerFactory;
use Shlinkio\Shlink\Common\Factory\TranslatorFactory;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Shlinkio\Shlink\Common\Service\IpLocationResolver;
use Shlinkio\Shlink\Common\Twig\Extension\TranslatorExtension;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            EntityManager::class => EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Cache::class => CacheFactory::class,
            IpLocationResolver::class => AnnotatedFactory::class,
            Translator::class => TranslatorFactory::class,
            TranslatorExtension::class => AnnotatedFactory::class,
            LocaleMiddleware::class => AnnotatedFactory::class,

            ErrorHandler\ContentBasedErrorHandler::class => AnnotatedFactory::class,
            ErrorHandler\ErrorHandlerManager::class => ErrorHandler\ErrorHandlerManagerFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            'translator' => Translator::class,
            AnnotatedFactory::CACHE_SERVICE => Cache::class,
        ],
    ],

];
