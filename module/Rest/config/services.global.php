<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\Middleware;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            Action\AuthenticateMiddleware::class => AnnotatedFactory::class,
            Action\CreateShortcodeMiddleware::class => AnnotatedFactory::class,
            Action\ResolveUrlMiddleware::class => AnnotatedFactory::class,
            Action\GetVisitsMiddleware::class => AnnotatedFactory::class,
            Action\ListShortcodesMiddleware::class => AnnotatedFactory::class,

            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => AnnotatedFactory::class,
        ],
    ],

];
