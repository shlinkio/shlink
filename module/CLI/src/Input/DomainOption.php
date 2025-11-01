<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final readonly class DomainOption
{
    private const string NAME = 'domain';

    public function __construct(Command $command, string $description)
    {
        $command->addOption(
            name: self::NAME,
            shortcut: 'd',
            mode: InputOption::VALUE_REQUIRED,
            description: $description,
        );
    }

    public function get(InputInterface $input): string|null
    {
        return $input->getOption(self::NAME);
    }
}
