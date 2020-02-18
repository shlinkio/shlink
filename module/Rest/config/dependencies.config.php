<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

return [

    'dependencies' => [
        'factories' => [
            ApiKeyService::class => ConfigAbstractFactory::class,

            Action\HealthAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\CreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\EditShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\DeleteShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ResolveShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ListShortUrlsAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\EditShortUrlTagsAction::class => ConfigAbstractFactory::class,
            Action\Visit\GetVisitsAction::class => ConfigAbstractFactory::class,
            Action\Tag\ListTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\DeleteTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\CreateTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\UpdateTagAction::class => ConfigAbstractFactory::class,

            ImplicitOptionsMiddleware::class => Middleware\EmptyResponseImplicitOptionsMiddlewareFactory::class,
            Middleware\BodyParserMiddleware::class => InvokableFactory::class,
            Middleware\CrossDomainMiddleware::class => InvokableFactory::class,
            Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class => InvokableFactory::class,
            Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class => ConfigAbstractFactory::class,
            Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ApiKeyService::class => ['em'],

        Action\HealthAction::class => [Connection::class, AppOptions::class, 'Logger_Shlink'],
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
        Action\ShortUrl\ResolveShortUrlAction::class => [
            Service\ShortUrl\ShortUrlResolver::class,
            'config.url_shortener.domain',
        ],
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

        Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class => ['config.url_shortener.domain.hostname'],
        Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class => [
            'config.url_shortener.default_short_codes_length',
        ],
    ],

];
