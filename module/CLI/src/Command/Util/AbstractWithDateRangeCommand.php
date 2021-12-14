<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

use Cake\Chronos\Chronos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_string;
use function sprintf;

abstract class AbstractWithDateRangeCommand extends Command
{
    private const START_DATE = 'start-date';
    private const END_DATE = 'end-date';

    final protected function configure(): void
    {
        $this->doConfigure();
        $this
            ->addOption(self::START_DATE, 's', InputOption::VALUE_REQUIRED, $this->getStartDateDesc(self::START_DATE))
            ->addOption(self::END_DATE, 'e', InputOption::VALUE_REQUIRED, $this->getEndDateDesc(self::END_DATE));
    }

    protected function getStartDateOption(InputInterface $input, OutputInterface $output): ?Chronos
    {
        return $this->getDateOption($input, $output, self::START_DATE);
    }

    protected function getEndDateOption(InputInterface $input, OutputInterface $output): ?Chronos
    {
        return $this->getDateOption($input, $output, self::END_DATE);
    }

    private function getDateOption(InputInterface $input, OutputInterface $output, string $key): ?Chronos
    {
        $value = $input->getOption($key);
        if (empty($value) || ! is_string($value)) {
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
                $this->getApplication()?->renderThrowable($e, $output);
            }

            return null;
        }
    }

    abstract protected function doConfigure(): void;

    abstract protected function getStartDateDesc(string $optionName): string;

    abstract protected function getEndDateDesc(string $optionName): string;
}
