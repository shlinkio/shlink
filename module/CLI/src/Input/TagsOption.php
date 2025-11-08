<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function array_unique;

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
            );
    }

    /**
     * Whether tags have been set or not, via `--tag` or `-t`
     */
    public function exists(InputInterface $input): bool
    {
        return $input->hasParameterOption(['--tag', '-t']);
    }

    /**
     * @return string[]
     */
    public function get(InputInterface $input): array
    {
        return array_unique($input->getOption('tag'));
    }
}
