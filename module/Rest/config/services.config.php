<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Service;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'services' => [
        'factories' => [
            Service\RestTokenService::class => AnnotatedFactory::class,

            Action\AuthenticateAction::class => AnnotatedFactory::class,
            Action\CreateShortcodeAction::class => AnnotatedFactory::class,
            Action\ResolveUrlAction::class => AnnotatedFactory::class,
            Action\GetVisitsAction::class => AnnotatedFactory::class,
            Action\ListShortcodesAction::class => AnnotatedFactory::class,

            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => AnnotatedFactory::class,
            Middleware\NotFoundMiddleware::class => AnnotatedFactory::class,
        ],
    ],

];
