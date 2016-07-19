<?php
use Acelaya\UrlShortener\Middleware;
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\CLI;
use Symfony\Component\Console;

return [

    'services' => [
        'factories' => [
            Console\Application::class => CLI\Factory\ApplicationFactory::class,

            CLI\Command\GenerateShortcodeCommand::class => AnnotatedFactory::class,
            CLI\Command\ResolveUrlCommand::class => AnnotatedFactory::class,
            CLI\Command\ListShortcodesCommand::class => AnnotatedFactory::class,
            CLI\Command\GetVisitsCommand::class => AnnotatedFactory::class,
        ],
    ],

];
