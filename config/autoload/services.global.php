<?php
use Acelaya\UrlShortener\CLI;
use Acelaya\UrlShortener\Factory\CacheFactory;
use Acelaya\UrlShortener\Factory\EntityManagerFactory;
use Acelaya\UrlShortener\Middleware;
use Acelaya\UrlShortener\Service;
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console;
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Helper;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Expressive\Twig;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            Expressive\Application::class => Container\ApplicationFactory::class,
            Console\Application::class => CLI\Factory\ApplicationFactory::class,

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
            Service\ShortUrlService::class => AnnotatedFactory::class,
            Service\RestTokenService::class => AnnotatedFactory::class,
            Cache::class => CacheFactory::class,

            // Cli commands
            CLI\Command\GenerateShortcodeCommand::class => AnnotatedFactory::class,
            CLI\Command\ResolveUrlCommand::class => AnnotatedFactory::class,

            // Middleware
            Middleware\Routable\RedirectMiddleware::class => AnnotatedFactory::class,
            Middleware\Rest\AuthenticateMiddleware::class => AnnotatedFactory::class,
            Middleware\Rest\CreateShortcodeMiddleware::class => AnnotatedFactory::class,
            Middleware\Rest\ResolveUrlMiddleware::class => AnnotatedFactory::class,
            Middleware\Rest\GetVisitsMiddleware::class => AnnotatedFactory::class,
            Middleware\Rest\ListShortcodesMiddleware::class => AnnotatedFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => AnnotatedFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
            'httpClient' => GuzzleHttp\Client::class,
            Router\RouterInterface::class => Router\FastRouteRouter::class,
            AnnotatedFactory::CACHE_SERVICE => Cache::class,
        ]
    ],

];
