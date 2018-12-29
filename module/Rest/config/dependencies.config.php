<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            Authentication\JWTService::class => ConfigAbstractFactory::class,
            ApiKeyService::class => ConfigAbstractFactory::class,

            Action\AuthenticateAction::class => ConfigAbstractFactory::class,
            Action\HealthAction::class => Action\HealthActionFactory::class,
            Action\ShortUrl\CreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\EditShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\DeleteShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ResolveShortUrlAction::class => ConfigAbstractFactory::class,
            Action\Visit\GetVisitsAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ListShortUrlsAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\EditShortUrlTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\ListTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\DeleteTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\CreateTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\UpdateTagAction::class => ConfigAbstractFactory::class,

            Middleware\BodyParserMiddleware::class => InvokableFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\PathVersionMiddleware::class => InvokableFactory::class,
            Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class => InvokableFactory::class,
            Middleware\ShortUrl\ShortCodePathMiddleware::class => InvokableFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Authentication\JWTService::class => [AppOptions::class],
        ApiKeyService::class => ['em'],

        Action\AuthenticateAction::class => [ApiKeyService::class, Authentication\JWTService::class, 'Logger_Shlink'],
        Action\ShortUrl\CreateShortUrlAction::class => [
            Service\UrlShortener::class,
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortUrl\SingleStepCreateShortUrlAction::class => [
            Service\UrlShortener::class,
            ApiKeyService::class,
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortUrl\EditShortUrlAction::class => [Service\ShortUrlService::class, 'Logger_Shlink'],
        Action\ShortUrl\DeleteShortUrlAction::class => [Service\ShortUrl\DeleteShortUrlService::class, 'Logger_Shlink'],
        Action\ShortUrl\ResolveShortUrlAction::class => [Service\UrlShortener::class, 'config.url_shortener.domain'],
        Action\Visit\GetVisitsAction::class => [Service\VisitsTracker::class, 'Logger_Shlink'],
        Action\ShortUrl\ListShortUrlsAction::class => [
            Service\ShortUrlService::class,
            'config.url_shortener.domain',
            'Logger_Shlink',
        ],
        Action\ShortUrl\EditShortUrlTagsAction::class => [Service\ShortUrlService::class, 'Logger_Shlink'],
        Action\Tag\ListTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\DeleteTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\CreateTagsAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
        Action\Tag\UpdateTagAction::class => [Service\Tag\TagService::class, LoggerInterface::class],
    ],

];
