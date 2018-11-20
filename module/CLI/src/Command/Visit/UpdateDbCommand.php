<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->setDescription('Updates the GeoLite2 database file used to geolocate IP addresses')
            ->setHelp(
                'The GeoLite2 database is updated first Tuesday every month, so this command should be ideally run '
                . 'every first Wednesday'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
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
            $io->writeln('');

            $io->success('GeoLite2 database properly updated');
        } catch (RuntimeException $e) {
            $progressBar->finish();
            $io->writeln('');

            $io->error('An error occurred while updating GeoLite2 database');
            if ($io->isVerbose()) {
                $this->getApplication()->renderException($e, $output);
            }
        }
    }
}
