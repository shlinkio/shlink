<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DownloadGeoLiteDbCommand extends Command
{
    public const NAME = 'visit:download-db';

    private GeolocationDbUpdaterInterface $dbUpdater;
    private ?ProgressBar $progressBar = null;

    public function __construct(GeolocationDbUpdaterInterface $dbUpdater)
    {
        parent::__construct();
        $this->dbUpdater = $dbUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                'Checks if the GeoLite2 db file is too old or it does not exist, and tries to download an up-to-date '
                . 'copy if so.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->dbUpdater->checkDbUpdate(function (bool $olderDbExists) use ($io): void {
                $io->text(sprintf('<fg=blue>%s GeoLite2 db file...</>', $olderDbExists ? 'Updating' : 'Downloading'));
                $this->progressBar = new ProgressBar($io);
            }, function (int $total, int $downloaded): void {
                $this->progressBar->setMaxSteps($total);
                $this->progressBar->setProgress($downloaded);
            });

            if ($this->progressBar === null) {
                $io->info('GeoLite2 db file is up to date.');
            } else {
                $this->progressBar->finish();
                $io->success('GeoLite2 db file properly downloaded.');
            }

            return ExitCodes::EXIT_SUCCESS;
        } catch (GeolocationDbUpdateFailedException $e) {
            $olderDbExists = $e->olderDbExists();

            if ($olderDbExists) {
                $io->warning(
                    'GeoLite2 db file update failed. Visits will continue to be located with the old version.',
                );
            } else {
                $io->error('GeoLite2 db file download failed. It will not be possible to locate visits.');
            }

            if ($io->isVerbose()) {
                $this->getApplication()->renderThrowable($e, $io);
            }

            return $olderDbExists ? ExitCodes::EXIT_WARNING : ExitCodes::EXIT_FAILURE;
        }
    }
}
