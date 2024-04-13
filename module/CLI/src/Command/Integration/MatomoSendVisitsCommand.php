<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Integration;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSenderInterface;
use Shlinkio\Shlink\Core\Matomo\VisitSendingProgressTrackerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function Shlinkio\Shlink\Common\buildDateRange;
use function sprintf;

class MatomoSendVisitsCommand extends Command
{
    public const NAME = 'integration:matomo:send-visits';

    private readonly bool $matomoEnabled;

    public function __construct(MatomoOptions $matomoOptions, private readonly MatomoVisitSenderInterface $visitSender)
    {
        $this->matomoEnabled = $matomoOptions->enabled;
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<HELP
        This command allows you to send existing visits from this Shlink instance to the configured Matomo server.
        
        Its intention is to allow you to configure Matomo at some point in time, and still have your whole visits 
        history tracked there.
        
        This command will unconditionally send to Matomo all visits for a specific date range, so make sure you 
        provide the proper limits to avoid duplicated visits.
        
        Send all visits created so far:
            <info>%command.name%</info>
        
        Send all visits created before 2024:
            <info>%command.name% --until 2023-12-31</info>

        Send all visits created after a specific day:
            <info>%command.name% --since 2022-03-27</info>

        Send all visits created during 2022:
            <info>%command.name% --since 2022-01-01 --until 2022-12-31</info>
        HELP;

        $this
            ->setName(self::NAME)
            ->setDescription(sprintf(
                '%sSend existing visits to the configured matomo instance',
                $this->matomoEnabled ? '' : '[MATOMO INTEGRATION DISABLED] ',
            ))
            ->setHelp($help)
            ->addOption(
                'since',
                's',
                InputOption::VALUE_REQUIRED,
                'Only visits created since this date, inclusively, will be sent to Matomo',
            )
            ->addOption(
                'until',
                'u',
                InputOption::VALUE_REQUIRED,
                'Only visits created until this date, inclusively, will be sent to Matomo',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! $this->matomoEnabled) {
            $io->warning('Matomo integration is not enabled in this Shlink instance');
            return ExitCode::EXIT_WARNING;
        }

        // TODO Validate provided date formats
        $since = $input->getOption('since');
        $until = $input->getOption('until');
        $dateRange = buildDateRange(
            startDate: $since !== null ? Chronos::parse($since) : null,
            endDate: $until !== null ? Chronos::parse($until) : null,
        );

        if ($input->isInteractive()) {
            // TODO Display the resolved date range in case it didn't fail to parse but the value was incorrect
            $io->warning([
                'You are about to send visits in this Shlink instance to Matomo',
                'Shlink will not check for already sent visits, which could result in some duplications. Make sure '
                . 'you have verified only visits in the right date range are going to be sent.',
            ]);
            if (! $io->confirm('Continue?', default: false)) {
                return ExitCode::EXIT_WARNING;
            }
        }

        $result = $this->visitSender->sendVisitsInDateRange(
            $dateRange,
            new class ($io, $this->getApplication()) implements VisitSendingProgressTrackerInterface {
                public function __construct(private readonly SymfonyStyle $io, private readonly ?Application $app)
                {
                }

                public function success(int $index): void
                {
                    $this->io->write('.');
                }

                public function error(int $index, Throwable $e): void
                {
                    $this->io->write('<error>E</error>');
                    if ($this->io->isVerbose()) {
                        $this->app?->renderThrowable($e, $this->io);
                    }
                }
            },
        );

        match (true) {
            $result->hasFailures() && $result->hasSuccesses() => $io->warning(
                sprintf('%s visits sent to Matomo. %s failed', $result->successfulVisits, $result->failedVisits),
            ),
            $result->hasFailures() => $io->error(
                sprintf('%s visits failed to be sent to Matomo.', $result->failedVisits),
            ),
            $result->hasSuccesses() => $io->success(sprintf('%s visits sent to Matomo.', $result->successfulVisits)),
            default => $io->info('There was no visits matching provided date range'),
        };

        return ExitCode::EXIT_SUCCESS;
    }
}
