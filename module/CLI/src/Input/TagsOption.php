<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function array_map;
use function array_unique;
use function Shlinkio\Shlink\Core\ArrayUtils\flatten;
use function Shlinkio\Shlink\Core\splitByComma;

readonly class TagsOption
{
    public function __construct(Command $command, string $description)
    {
        $command
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $description,
            )
            ->addOption(
                'tags',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: '[DEPRECATED] Use --tag instead',
            );
    }

    /**
     * Whether tags have been set or not, via `--tag`, `-t` or the deprecated `--tags`
     */
    public function exists(InputInterface $input): bool
    {
        return $input->hasParameterOption(['--tag', '-t']) || $input->hasParameterOption('--tags');
    }

    /**
     * @return string[]
     */
    public function get(InputInterface $input): array
    {
        // FIXME DEPRECATED Remove support for comma-separated tags in next major release
        $tags = [...$input->getOption('tag'), ...$input->getOption('tags')];
        return array_unique(flatten(array_map(splitByComma(...), $tags)));
    }
}
