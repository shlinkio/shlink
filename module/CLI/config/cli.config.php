<?php
use Shlinkio\Shlink\CLI\Command;

return [

    'cli' => [
        'locale' => env('CLI_LOCALE', 'en'),
        'commands' => [
            Command\Shortcode\GenerateShortcodeCommand::class,
            Command\Shortcode\ResolveUrlCommand::class,
            Command\Shortcode\ListShortcodesCommand::class,
            Command\Shortcode\GetVisitsCommand::class,
            Command\Shortcode\GeneratePreviewCommand::class,
            Command\Visit\ProcessVisitsCommand::class,
            Command\Config\GenerateCharsetCommand::class,
            Command\Config\GenerateSecretCommand::class,
            Command\Api\GenerateKeyCommand::class,
            Command\Api\DisableKeyCommand::class,
            Command\Api\ListKeysCommand::class,
            Command\Tag\ListTagsCommand::class,
            Command\Tag\CreateTagCommand::class,
            Command\Tag\RenameTagCommand::class,
        ]
    ],

];
