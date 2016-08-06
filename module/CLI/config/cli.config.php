<?php
use Shlinkio\Shlink\CLI\Command;

return [

    'cli' => [
        'commands' => [
            Command\GenerateShortcodeCommand::class,
            Command\ResolveUrlCommand::class,
            Command\ListShortcodesCommand::class,
            Command\GetVisitsCommand::class,
            Command\ProcessVisitsCommand::class,
            Command\Config\GenerateCharsetCommand::class,
            Command\Api\GenerateKeyCommand::class,
        ]
    ],

];
