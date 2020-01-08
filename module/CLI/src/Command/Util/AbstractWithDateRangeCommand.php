<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

use Cake\Chronos\Chronos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function sprintf;

abstract class AbstractWithDateRangeCommand extends Command
{
    final protected function configure(): void
    {
        $this->doConfigure();
        $this
            ->addOption('startDate', 's', InputOption::VALUE_REQUIRED, $this->getStartDateDesc())
            ->addOption('endDate', 'e', InputOption::VALUE_REQUIRED, $this->getEndDateDesc());
    }

    protected function getDateOption(InputInterface $input, OutputInterface $output, string $key): ?Chronos
    {
        $value = $input->getOption($key);
        if (empty($value)) {
            return null;
        }

        try {
            return Chronos::parse($value);
        } catch (Throwable $e) {
            $output->writeln(sprintf(
                '<comment>> Ignored provided "%s" since its value "%s" is not a valid date. <</comment>',
                $key,
                $value,
            ));

            if ($output->isVeryVerbose()) {
                $this->getApplication()->renderThrowable($e, $output);
            }

            return null;
        }
    }

    abstract protected function doConfigure(): void;

    abstract protected function getStartDateDesc(): string;
    abstract protected function getEndDateDesc(): string;
}
