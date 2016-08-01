<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\CLI\Command;
use Shlinkio\Shlink\CLI\Factory\ApplicationFactory;
use Symfony\Component\Console\Application;

return [

    'dependencies' => [
        'factories' => [
            Application::class => ApplicationFactory::class,

            Command\GenerateShortcodeCommand::class => AnnotatedFactory::class,
            Command\ResolveUrlCommand::class => AnnotatedFactory::class,
            Command\ListShortcodesCommand::class => AnnotatedFactory::class,
            Command\GetVisitsCommand::class => AnnotatedFactory::class,
            Command\ProcessVisitsCommand::class => AnnotatedFactory::class,
            Command\ProcessVisitsCommand::class => AnnotatedFactory::class,
            Command\Config\GenerateCharsetCommand::class => AnnotatedFactory::class,
        ],
    ],

];
