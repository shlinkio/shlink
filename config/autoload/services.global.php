<?php
use Acelaya\UrlShortener\Middleware;
use Acelaya\UrlShortener\Service;
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
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
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Service\ShortUrlService::class => AnnotatedFactory::class,

            // Middleware
            Middleware\Routable\RedirectMiddleware::class => AnnotatedFactory::class,
        ],
        'aliases' => [
            Router\RouterInterface::class => Router\FastRouteRouter::class,
        ],
    ],

];
