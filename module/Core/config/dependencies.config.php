<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service;

return [

    'dependencies' => [
        'factories' => [
            AppOptions::class => AnnotatedFactory::class,

            // Services
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Service\ShortUrlService::class => AnnotatedFactory::class,
            Service\VisitService::class => AnnotatedFactory::class,

            // Middleware
            Action\RedirectAction::class => AnnotatedFactory::class,
            Action\QrCodeAction::class => AnnotatedFactory::class,
        ],
    ],

];
