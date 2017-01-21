<?php
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Expressive\Twig;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            Expressive\Application::class => Container\ApplicationFactory::class,
            Router\FastRouteRouter::class => InvokableFactory::class,
            Template\TemplateRendererInterface::class => Twig\TwigRendererFactory::class,
            \Twig_Environment::class => Twig\TwigEnvironmentFactory::class,
        ],
        'aliases' => [
            Router\RouterInterface::class => Router\FastRouteRouter::class,
        ],
    ],

];
