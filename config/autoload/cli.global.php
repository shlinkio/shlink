<?php
use Acelaya\UrlShortener\CLI\Command;

return [

    'cli' => [
        'commands' => [
            Command\GenerateShortcodeCommand::class,
            Command\ResolveUrlCommand::class,
            Command\ListShortcodesCommand::class,
            Command\GetVisitsCommand::class,
        ]
    ],

];
