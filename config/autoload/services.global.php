<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Common\Expressive\ContentBasedErrorHandler;
use Shlinkio\Shlink\Common\Expressive\ErrorHandlerManager;
use Shlinkio\Shlink\Common\Expressive\ErrorHandlerManagerFactory;
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
            ContentBasedErrorHandler::class => AnnotatedFactory::class,
            ErrorHandlerManager::class => ErrorHandlerManagerFactory::class,
            Template\TemplateRendererInterface::class => Twig\TwigRendererFactory::class,
        ],
        'aliases' => [
            Router\RouterInterface::class => Router\FastRouteRouter::class,
            'Zend\Expressive\FinalHandler' => ContentBasedErrorHandler::class,
        ],
    ],

];
