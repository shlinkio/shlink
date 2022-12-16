<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Mercure\LcobucciJwtProvider;
use Shlinkio\Shlink\Core\Domain\DomainService;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Core\Tag\TagService;
use Shlinkio\Shlink\Core\Visit;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;

return [

    'dependencies' => [
        'factories' => [
            ApiKeyService::class => ConfigAbstractFactory::class,

            Action\HealthAction::class => ConfigAbstractFactory::class,
            Action\MercureInfoAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\CreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\EditShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\DeleteShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ResolveShortUrlAction::class => ConfigAbstractFactory::class,
            Action\ShortUrl\ListShortUrlsAction::class => ConfigAbstractFactory::class,
            Action\Visit\ShortUrlVisitsAction::class => ConfigAbstractFactory::class,
            Action\Visit\TagVisitsAction::class => ConfigAbstractFactory::class,
            Action\Visit\DomainVisitsAction::class => ConfigAbstractFactory::class,
            Action\Visit\GlobalVisitsAction::class => ConfigAbstractFactory::class,
            Action\Visit\OrphanVisitsAction::class => ConfigAbstractFactory::class,
            Action\Visit\NonOrphanVisitsAction::class => ConfigAbstractFactory::class,
            Action\Tag\ListTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\TagsStatsAction::class => ConfigAbstractFactory::class,
            Action\Tag\DeleteTagsAction::class => ConfigAbstractFactory::class,
            Action\Tag\UpdateTagAction::class => ConfigAbstractFactory::class,
            Action\Domain\ListDomainsAction::class => ConfigAbstractFactory::class,
            Action\Domain\DomainRedirectsAction::class => ConfigAbstractFactory::class,

            ImplicitOptionsMiddleware::class => Middleware\EmptyResponseImplicitOptionsMiddlewareFactory::class,
            Middleware\BodyParserMiddleware::class => InvokableFactory::class,
            Middleware\CrossDomainMiddleware::class => ConfigAbstractFactory::class,
            Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware::class => InvokableFactory::class,
            Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class => ConfigAbstractFactory::class,
            Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class => ConfigAbstractFactory::class,
            Middleware\ShortUrl\OverrideDomainMiddleware::class => ConfigAbstractFactory::class,
            Middleware\Mercure\NotConfiguredMercureErrorHandler::class => ConfigAbstractFactory::class,
            Middleware\ErrorHandler\BackwardsCompatibleProblemDetailsHandler::class => InvokableFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ApiKeyService::class => ['em'],

        Action\HealthAction::class => ['em', Options\AppOptions::class],
        Action\MercureInfoAction::class => [LcobucciJwtProvider::class, 'config.mercure'],
        Action\ShortUrl\CreateShortUrlAction::class => [
            ShortUrl\UrlShortener::class,
            ShortUrlDataTransformer::class,
            Options\UrlShortenerOptions::class,
        ],
        Action\ShortUrl\SingleStepCreateShortUrlAction::class => [
            ShortUrl\UrlShortener::class,
            ShortUrlDataTransformer::class,
            Options\UrlShortenerOptions::class,
        ],
        Action\ShortUrl\EditShortUrlAction::class => [ShortUrl\ShortUrlService::class, ShortUrlDataTransformer::class],
        Action\ShortUrl\DeleteShortUrlAction::class => [ShortUrl\DeleteShortUrlService::class],
        Action\ShortUrl\ResolveShortUrlAction::class => [
            ShortUrl\ShortUrlResolver::class,
            ShortUrlDataTransformer::class,
        ],
        Action\Visit\ShortUrlVisitsAction::class => [Visit\VisitsStatsHelper::class],
        Action\Visit\TagVisitsAction::class => [Visit\VisitsStatsHelper::class],
        Action\Visit\DomainVisitsAction::class => [
            Visit\VisitsStatsHelper::class,
            'config.url_shortener.domain.hostname',
        ],
        Action\Visit\GlobalVisitsAction::class => [Visit\VisitsStatsHelper::class],
        Action\Visit\OrphanVisitsAction::class => [
            Visit\VisitsStatsHelper::class,
            Visit\Transformer\OrphanVisitDataTransformer::class,
        ],
        Action\Visit\NonOrphanVisitsAction::class => [Visit\VisitsStatsHelper::class],
        Action\ShortUrl\ListShortUrlsAction::class => [
            ShortUrl\ShortUrlListService::class,
            ShortUrlDataTransformer::class,
        ],
        Action\Tag\ListTagsAction::class => [TagService::class],
        Action\Tag\TagsStatsAction::class => [TagService::class],
        Action\Tag\DeleteTagsAction::class => [TagService::class],
        Action\Tag\UpdateTagAction::class => [TagService::class],
        Action\Domain\ListDomainsAction::class => [DomainService::class, Options\NotFoundRedirectOptions::class],
        Action\Domain\DomainRedirectsAction::class => [DomainService::class],

        Middleware\CrossDomainMiddleware::class => ['config.cors'],
        Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware::class => ['config.url_shortener.domain.hostname'],
        Middleware\ShortUrl\DefaultShortCodesLengthMiddleware::class => [
            'config.url_shortener.default_short_codes_length',
        ],
        Middleware\ShortUrl\OverrideDomainMiddleware::class => [DomainService::class],
        Middleware\Mercure\NotConfiguredMercureErrorHandler::class => [
            ProblemDetailsResponseFactory::class,
            LoggerInterface::class,
        ],
    ],

];
