<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Service;

return [

    'dependencies' => [
        'factories' => [
            // Services
            Service\UrlShortener::class => AnnotatedFactory::class,
            Service\VisitsTracker::class => AnnotatedFactory::class,
            Service\ShortUrlService::class => AnnotatedFactory::class,
            Service\VisitService::class => AnnotatedFactory::class,

            // Middleware
            RedirectAction::class => AnnotatedFactory::class,
        ],
    ],

];
