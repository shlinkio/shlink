<?php
declare(strict_types=1);

use Shlinkio\Shlink\Common\Factory\EmptyResponseImplicitOptionsMiddlewareFactory;
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Helper;
use Zend\Expressive\Middleware;
use Zend\Expressive\Plates;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'dependencies' => [
        'factories' => [
            Expressive\Application::class => Container\ApplicationFactory::class,
            Template\TemplateRendererInterface::class => Plates\PlatesRendererFactory::class,
            Router\RouterInterface::class => Router\FastRouteRouterFactory::class,
            ErrorHandler::class => Container\ErrorHandlerFactory::class,
            Middleware\ImplicitOptionsMiddleware::class => EmptyResponseImplicitOptionsMiddlewareFactory::class,

            Helper\UrlHelper::class => Helper\UrlHelperFactory::class,
            Helper\ServerUrlHelper::class => InvokableFactory::class,
        ],
    ],

];
