<?php
declare(strict_types=1);

use Shlinkio\Shlink\CLI\Command;
use Shlinkio\Shlink\Common;

return [

    'cli' => [
        'locale' => Common\env('CLI_LOCALE', 'en'),
        'commands' => [
            Command\Shortcode\GenerateShortcodeCommand::NAME => Command\Shortcode\GenerateShortcodeCommand::class,
            Command\Shortcode\ResolveUrlCommand::NAME => Command\Shortcode\ResolveUrlCommand::class,
            Command\Shortcode\ListShortcodesCommand::NAME => Command\Shortcode\ListShortcodesCommand::class,
            Command\Shortcode\GetVisitsCommand::NAME => Command\Shortcode\GetVisitsCommand::class,
            Command\Shortcode\GeneratePreviewCommand::NAME => Command\Shortcode\GeneratePreviewCommand::class,

            Command\Visit\ProcessVisitsCommand::NAME => Command\Visit\ProcessVisitsCommand::class,

            Command\Config\GenerateCharsetCommand::NAME => Command\Config\GenerateCharsetCommand::class,
            Command\Config\GenerateSecretCommand::NAME => Command\Config\GenerateSecretCommand::class,

            Command\Api\GenerateKeyCommand::NAME => Command\Api\GenerateKeyCommand::class,
            Command\Api\DisableKeyCommand::NAME => Command\Api\DisableKeyCommand::class,
            Command\Api\ListKeysCommand::NAME => Command\Api\ListKeysCommand::class,

            Command\Tag\ListTagsCommand::NAME => Command\Tag\ListTagsCommand::class,
            Command\Tag\CreateTagCommand::NAME => Command\Tag\CreateTagCommand::class,
            Command\Tag\RenameTagCommand::NAME => Command\Tag\RenameTagCommand::class,
            Command\Tag\DeleteTagsCommand::NAME => Command\Tag\DeleteTagsCommand::class,
        ],
    ],

];
