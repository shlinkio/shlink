<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/** @deprecated */
class UpdateDbCommand extends Command
{
    public const NAME = 'visit:update-db';

    /** @var DbUpdaterInterface */
    private $geoLiteDbUpdater;

    public function __construct(DbUpdaterInterface $geoLiteDbUpdater)
    {
        parent::__construct();
        $this->geoLiteDbUpdater = $geoLiteDbUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('[DEPRECATED] Updates the GeoLite2 database file used to geolocate IP addresses')
            ->setHelp(
                'The GeoLite2 database is updated first Tuesday every month, so this command should be ideally run '
                . 'every first Wednesday'
            )
            ->addOption(
                'ignoreErrors',
                'i',
                InputOption::VALUE_NONE,
                'Makes the command success even iof the update fails.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = new ProgressBar($output);
        $progressBar->start();

        try {
            $this->geoLiteDbUpdater->downloadFreshCopy(function (int $total, int $downloaded) use ($progressBar) {
                $progressBar->setMaxSteps($total);
                $progressBar->setProgress($downloaded);
            });

            $progressBar->finish();
            $io->newLine();

            $io->success('GeoLite2 database properly updated');
            return ExitCodes::EXIT_SUCCESS;
        } catch (RuntimeException $e) {
            $progressBar->finish();
            $io->newLine();

            return $this->handleError($e, $io, $input);
        }
    }

    private function handleError(RuntimeException $e, SymfonyStyle $io, InputInterface $input): int
    {
        $ignoreErrors = $input->getOption('ignoreErrors');
        $baseErrorMsg = 'An error occurred while updating GeoLite2 database';

        if ($ignoreErrors) {
            $io->warning(sprintf('%s, but it was ignored', $baseErrorMsg));
            return ExitCodes::EXIT_SUCCESS;
        }

        $io->error($baseErrorMsg);
        if ($io->isVerbose()) {
            $this->getApplication()->renderException($e, $io);
        }
        return ExitCodes::EXIT_FAILURE;
    }
}
