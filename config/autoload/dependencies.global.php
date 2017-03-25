<?php
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Expressive\Twig;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'dependencies' => [
        'factories' => [
            Expressive\Application::class => Container\ApplicationFactory::class,
            Template\TemplateRendererInterface::class => Twig\TwigRendererFactory::class,
            \Twig_Environment::class => Twig\TwigEnvironmentFactory::class,
            Router\RouterInterface::class => Router\FastRouteRouterFactory::class,
            ErrorHandler::class => Container\ErrorHandlerFactory::class,
        ],
    ],

];
