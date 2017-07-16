<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Middleware;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service;

return [

    'dependencies' => [
        'factories' => [
            Options\AppOptions::class => Options\AppOptionsFactory::class,

            // Services
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Service\ShortUrlService::class => AnnotatedFactory::class,
            Service\VisitService::class => AnnotatedFactory::class,
            Service\Tag\TagService::class => AnnotatedFactory::class,

            // Middleware
            Action\RedirectAction::class => AnnotatedFactory::class,
            Action\QrCodeAction::class => AnnotatedFactory::class,
            Action\PreviewAction::class => AnnotatedFactory::class,
            Middleware\QrCodeCacheMiddleware::class => AnnotatedFactory::class,
        ],
    ],

];
