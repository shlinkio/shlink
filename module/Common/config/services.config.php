<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Shlinkio\Shlink\Common\Factory\CacheFactory;
use Shlinkio\Shlink\Common\Factory\EntityManagerFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            EntityManager::class => EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Cache::class => CacheFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            AnnotatedFactory::CACHE_SERVICE => Cache::class,
        ],
    ],

];
