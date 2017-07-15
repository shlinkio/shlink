<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Service;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            JWTService::class => AnnotatedFactory::class,
            Service\ApiKeyService::class => AnnotatedFactory::class,

            Action\AuthenticateAction::class => AnnotatedFactory::class,
            Action\CreateShortcodeAction::class => AnnotatedFactory::class,
            Action\ResolveUrlAction::class => AnnotatedFactory::class,
            Action\GetVisitsAction::class => AnnotatedFactory::class,
            Action\ListShortcodesAction::class => AnnotatedFactory::class,
            Action\EditShortcodeTagsAction::class => AnnotatedFactory::class,
            Action\Tag\ListTagsAction::class => AnnotatedFactory::class,
            Action\Tag\DeleteTagsAction::class => AnnotatedFactory::class,
            Action\Tag\CreateTagsAction::class => AnnotatedFactory::class,
            Action\Tag\UpdateTagAction::class => AnnotatedFactory::class,

            Middleware\BodyParserMiddleware::class => AnnotatedFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\PathVersionMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => AnnotatedFactory::class,
        ],
    ],

];
