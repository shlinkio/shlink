<?php
declare(strict_types=1);

use Shlinkio\Shlink\Common\Factory\EmptyResponseImplicitOptionsMiddlewareFactory;
use Zend\Expressive;
use Zend\Expressive\Container;
use Zend\Expressive\Helper;
use Zend\Expressive\Middleware;
use Zend\Expressive\Plates;
use Zend\Expressive\Router;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Template;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'dependencies' => [
        'factories' => [
            ImplicitOptionsMiddleware::class => EmptyResponseImplicitOptionsMiddlewareFactory::class,

            Helper\UrlHelper::class => Helper\UrlHelperFactory::class,
            Helper\ServerUrlHelper::class => InvokableFactory::class,
        ],

        'delegators' => [
            Expressive\Application::class => [
                Container\ApplicationConfigInjectionDelegator::class,
            ],
        ],
    ],

];
