<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Option;

use Cake\Chronos\Chronos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_string;
use function sprintf;

class DateOption
{
    public function __construct(
        private readonly Command $command,
        private readonly string $name,
        string $shortcut,
        string $description,
    ) {
        $command->addOption($name, $shortcut, InputOption::VALUE_REQUIRED, $description);
    }

    public function get(InputInterface $input, OutputInterface $output): ?Chronos
    {
        $value = $input->getOption($this->name);
        if (empty($value) || ! is_string($value)) {
            return null;
        }

        try {
            return Chronos::parse($value);
        } catch (Throwable $e) {
            $output->writeln(sprintf(
                '<comment>> Ignored provided "%s" since its value "%s" is not a valid date. <</comment>',
                $this->name,
                $value,
            ));

            if ($output->isVeryVerbose()) {
                $this->command->getApplication()?->renderThrowable($e, $output);
            }

            return null;
        }
    }
}
