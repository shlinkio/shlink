<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Integration;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSenderInterface;
use Shlinkio\Shlink\Core\Matomo\VisitSendingProgressTrackerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\dateRangeToHumanFriendly;
use function sprintf;

#[AsCommand(
    name: MatomoSendVisitsCommand::NAME,
    help: <<<HELP
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
        HELP
)]
class MatomoSendVisitsCommand extends Command implements VisitSendingProgressTrackerInterface
{
    public const string NAME = 'integration:matomo:send-visits';

    private readonly bool $matomoEnabled;
    private SymfonyStyle $io;

    public function __construct(MatomoOptions $matomoOptions, private readonly MatomoVisitSenderInterface $visitSender)
    {
        $this->matomoEnabled = $matomoOptions->enabled;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(sprintf(
            '%sSend existing visits to the configured matomo instance',
            $this->matomoEnabled ? '' : '<comment>[MATOMO INTEGRATION DISABLED]</comment> ',
        ));
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[Option('Only visits created since this date, inclusively, will be sent to Matomo', shortcut: 's')]
        string|null $since = null,
        #[Option('Only visits created until this date, inclusively, will be sent to Matomo', shortcut: 'u')]
        string|null $until = null,
    ): int {
        $this->io = $io;

        if (! $this->matomoEnabled) {
            $this->io->warning('Matomo integration is not enabled in this Shlink instance');
            return self::INVALID;
        }

        // TODO Validate provided date formats
        $dateRange = buildDateRange(
            startDate: $since !== null ? Chronos::parse($since) : null,
            endDate: $until !== null ? Chronos::parse($until) : null,
        );

        if ($input->isInteractive()) {
            $this->io->warning([
                'You are about to send visits from this Shlink instance to Matomo',
                'Resolved date range -> ' . dateRangeToHumanFriendly($dateRange),
                'Shlink will not check for already sent visits, which could result in some duplications. Make sure '
                . 'you have verified only visits in the right date range are going to be sent.',
            ]);
            if (! $this->io->confirm('Continue?', default: false)) {
                return self::INVALID;
            }
        }

        $result = $this->visitSender->sendVisitsInDateRange($dateRange, $this);

        match (true) {
            $result->hasFailures() && $result->hasSuccesses() => $this->io->warning(
                sprintf('%s visits sent to Matomo. %s failed.', $result->successfulVisits, $result->failedVisits),
            ),
            $result->hasFailures() => $this->io->error(
                sprintf('Failed to send %s visits to Matomo.', $result->failedVisits),
            ),
            $result->hasSuccesses() => $this->io->success(
                sprintf('%s visits sent to Matomo.', $result->successfulVisits),
            ),
            default => $this->io->info('There was no visits matching provided date range.'),
        };

        return self::SUCCESS;
    }

    public function success(int $index): void
    {
        $this->io->write('.');
    }

    public function error(int $index, Throwable $e): void
    {
        $this->io->write('<error>E</error>');
        if ($this->io->isVerbose()) {
            $this->getApplication()?->renderThrowable($e, $this->io);
        }
    }
}
