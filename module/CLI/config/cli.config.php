<?php
use Shlinkio\Shlink\CLI\Command;

return [

    'cli' => [
        'commands' => [
            Command\Shortcode\GenerateShortcodeCommand::class,
            Command\Shortcode\ResolveUrlCommand::class,
            Command\Shortcode\ListShortcodesCommand::class,
            Command\Shortcode\GetVisitsCommand::class,
            Command\ProcessVisitsCommand::class,
            Command\Config\GenerateCharsetCommand::class,
            Command\Config\GenerateSecretCommand::class,
            Command\Api\GenerateKeyCommand::class,
            Command\Api\DisableKeyCommand::class,
            Command\Api\ListKeysCommand::class,
        ]
    ],

];
