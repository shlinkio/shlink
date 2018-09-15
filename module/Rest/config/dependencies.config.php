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
            Action\ShortCode\CreateShortCodeAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\SingleStepCreateShortCodeAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\EditShortCodeAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\DeleteShortCodeAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\ResolveUrlAction::class => ConfigAbstractFactory::class,
            Action\Visit\GetVisitsAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\ListShortCodesAction::class => ConfigAbstractFactory::class,
            Action\ShortCode\EditShortCodeTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\ListTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\DeleteTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\CreateTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\UpdateTagAction::class => ConfigAbstractFactory::class,

            Middleware\BodyParserMiddleware::class => InvokableFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\PathVersionMiddleware::class => InvokableFactory::class,
            Middleware\CheckAuthenticationMiddleware::class => ConfigAbstractFactory::class,
            Middleware\ShortCode\CreateShortCodeContentNegotiationMiddleware::class => InvokableFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        JWTService::class => [AppOptions::class],
        ApiKeyService::class => ['em'],

        Action\AuthenticateAction::class => [ApiKeyService::class, JWTService::class, 'translator', 'Logger_Shlink'],
        Action\ShortCode\CreateShortCodeAction::class => [
            Service\UrlShortener::class,
            'translator',
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortCode\SingleStepCreateShortCodeAction::class => [
            Service\UrlShortener::class,
            'translator',
            ApiKeyService::class,
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortCode\EditShortCodeAction::class => [Service\ShortUrlService::class, 'translator', 'Logger_Shlink'],
        Action\ShortCode\DeleteShortCodeAction::class => [
            Service\ShortUrl\DeleteShortUrlService::class,
            'translator',
            'Logger_Shlink',
        ],
        Action\ShortCode\ResolveUrlAction::class => [
            Service\UrlShortener::class,
            'translator',
            'config.url_shortener.domain',
        ],
        Action\Visit\GetVisitsAction::class => [Service\VisitsTracker::class, 'translator', 'Logger_Shlink'],
        Action\ShortCode\ListShortCodesAction::class => [
            Service\ShortUrlService::class,
            'translator',
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortCode\EditShortCodeTagsAction::class => [
            Service\ShortUrlService::class,
            'translator',
            'Logger_Shlink',
        ],
        Action\Tag\ListTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\DeleteTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\CreateTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\UpdateTagAction::class => [Service\Tag\TagService::class, Translator::class, LoggerInterface::class],

        Middleware\CheckAuthenticationMiddleware::class => [
            JWTService::class,
            'translator',
            'config.auth.routes_whitelist',
            'Logger_Shlink',
        ],
    ],

];
