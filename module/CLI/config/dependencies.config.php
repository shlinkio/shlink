<?php
use Acelaya\ZsmAnnotatedServices\Factory\V3\AnnotatedFactory;
use Shlinkio\Shlink\CLI\Command;
use Shlinkio\Shlink\CLI\Factory\ApplicationFactory;
use Symfony\Component\Console\Application;

return [

    'dependencies' => [
        'factories' => [
            Application::class => ApplicationFactory::class,

            Command\Shortcode\GenerateShortcodeCommand::class => AnnotatedFactory::class,
            Command\Shortcode\ResolveUrlCommand::class => AnnotatedFactory::class,
            Command\Shortcode\ListShortcodesCommand::class => AnnotatedFactory::class,
            Command\Shortcode\GetVisitsCommand::class => AnnotatedFactory::class,
            Command\Shortcode\GeneratePreviewCommand::class => AnnotatedFactory::class,
            Command\Visit\ProcessVisitsCommand::class => AnnotatedFactory::class,
            Command\Config\GenerateCharsetCommand::class => AnnotatedFactory::class,
            Command\Config\GenerateSecretCommand::class => AnnotatedFactory::class,
            Command\Api\GenerateKeyCommand::class => AnnotatedFactory::class,
            Command\Api\DisableKeyCommand::class => AnnotatedFactory::class,
            Command\Api\ListKeysCommand::class => AnnotatedFactory::class,
            Command\Tag\ListTagsCommand::class => AnnotatedFactory::class,
            Command\Tag\CreateTagCommand::class => AnnotatedFactory::class,
        ],
    ],

];
