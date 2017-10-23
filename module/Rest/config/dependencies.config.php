<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service;
use Shlinkio\Shlink\Rest\Action;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Middleware;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            JWTService::class => ConfigAbstractFactory::class,
            ApiKeyService::class => ConfigAbstractFactory::class,

            Action\AuthenticateAction::class => ConfigAbstractFactory::class,
            Action\CreateShortcodeAction::class => ConfigAbstractFactory::class,
            Action\ResolveUrlAction::class => ConfigAbstractFactory::class,
            Action\GetVisitsAction::class => ConfigAbstractFactory::class,
            Action\ListShortcodesAction::class => ConfigAbstractFactory::class,
            Action\EditShortcodeTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\ListTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\DeleteTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\CreateTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\UpdateTagAction::class => ConfigAbstractFactory::class,

            Middleware\BodyParserMiddleware::class => InvokableFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\PathVersionMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        JWTService::class => [AppOptions::class],
        ApiKeyService::class => ['em'],

        Action\AuthenticateAction::class => [ApiKeyService::class, JWTService::class, 'translator', 'Logger_Shlink'],
        Action\CreateShortcodeAction::class => [
            Service\UrlShortener::class,
            'translator',
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ResolveUrlAction::class => [Service\UrlShortener::class, 'translator'],
        Action\GetVisitsAction::class => [Service\VisitsTracker::class, 'translator', 'Logger_Shlink'],
        Action\ListShortcodesAction::class => [Service\ShortUrlService::class, 'translator', 'Logger_Shlink'],
        Action\EditShortcodeTagsAction::class => [Service\ShortUrlService::class, 'translator', 'Logger_Shlink'],
        Action\Tag\ListTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\DeleteTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\CreateTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\UpdateTagAction::class => [Service\Tag\TagService::class, Translator::class, LoggerInterface::class],

        Middleware\CheckAuthenticationMiddleware::class => [JWTService::class, 'translator', 'Logger_Shlink'],
    ],

];
