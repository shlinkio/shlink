<?php
use Acelaya\UrlShortener\Factory\CacheFactory;
use Acelaya\UrlShortener\Factory\EntityManagerFactory;
use Acelaya\UrlShortener\Middleware;
use Acelaya\UrlShortener\Service;
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Zend\Expressive\Application;
use Zend\Expressive\Container;
use Zend\Expressive\Helper;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Expressive\Twig;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            Application::class => Container\ApplicationFactory::class,

            // Url helpers
            Helper\UrlHelper::class => Helper\UrlHelperFactory::class,
            Helper\ServerUrlMiddleware::class => Helper\ServerUrlMiddlewareFactory::class,
            Helper\UrlHelperMiddleware::class => Helper\UrlHelperMiddlewareFactory::class,
            Helper\ServerUrlHelper::class => InvokableFactory::class,
            Router\FastRouteRouter::class => InvokableFactory::class,

            // View
            'Zend\Expressive\FinalHandler' => Container\TemplatedErrorHandlerFactory::class,
            Template\TemplateRendererInterface::class => Twig\TwigRendererFactory::class,

            // Services
            EntityManager::class => EntityManagerFactory::class,
            GuzzleHttp\Client::class => InvokableFactory::class,
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Cache::class => CacheFactory::class,

            // Middleware
            Middleware\CliRoutable\GenerateShortcodeMiddleware::class => AnnotatedFactory::class,
            Middleware\Routable\RedirectMiddleware::class => AnnotatedFactory::class,
            Middleware\CliParamsMiddleware::class => Middleware\Factory\CliParamsMiddlewareFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            Router\RouterInterface::class => Router\FastRouteRouter::class,
        ]
    ],

];
