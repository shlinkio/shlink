<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Core\Action\RedirectMiddleware;
use Shlinkio\Shlink\Core\Service;

return [

    'services' => [
        'factories' => [
            // Services
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Service\ShortUrlService::class => AnnotatedFactory::class,
            Service\VisitService::class => AnnotatedFactory::class,

            // Middleware
            RedirectMiddleware::class => AnnotatedFactory::class,
        ],
    ],

];
