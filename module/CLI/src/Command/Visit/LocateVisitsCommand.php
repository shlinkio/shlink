<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Exception;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory as Locker;

use function sprintf;

class LocateVisitsCommand extends Command
{
    public const NAME = 'visit:locate';
    public const ALIASES = ['visit:process'];

    /** @var VisitServiceInterface */
    private $visitService;
    /** @var IpLocationResolverInterface */
    private $ipLocationResolver;
    /** @var Locker */
    private $locker;
    /** @var GeolocationDbUpdaterInterface */
    private $dbUpdater;

    /** @var SymfonyStyle */
    private $io;
    /** @var ProgressBar */
    private $progressBar;

    public function __construct(
        VisitServiceInterface $visitService,
        IpLocationResolverInterface $ipLocationResolver,
        Locker $locker,
        GeolocationDbUpdaterInterface $dbUpdater
    ) {
        parent::__construct();
        $this->visitService = $visitService;
        $this->ipLocationResolver = $ipLocationResolver;
        $this->locker = $locker;
        $this->dbUpdater = $dbUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Resolves visits origin locations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $lock = $this->locker->createLock(self::NAME);
        if (! $lock->acquire()) {
            $this->io->warning(sprintf('There is already an instance of the "%s" command in execution', self::NAME));
            return ExitCodes::EXIT_WARNING;
        }

        try {
            $this->checkDbUpdate();

            $this->visitService->locateUnlocatedVisits(
                [$this, 'getGeolocationDataForVisit'],
                static function (VisitLocation $location) use ($output) {
                    if (!$location->isEmpty()) {
                        $output->writeln(
                            sprintf(' [<info>Address located at "%s"</info>]', $location->getCountryName())
                        );
                    }
                }
            );

            $this->io->success('Finished processing all IPs');
            return ExitCodes::EXIT_SUCCESS;
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
            if ($this->io->isVerbose()) {
                $this->getApplication()->renderException($e, $this->io);
            }

            return ExitCodes::EXIT_FAILURE;
        } finally {
            $lock->release();
        }
    }

    public function getGeolocationDataForVisit(Visit $visit): Location
    {
        if (! $visit->hasRemoteAddr()) {
            $this->io->writeln(
                '<comment>Ignored visit with no IP address</comment>',
                OutputInterface::VERBOSITY_VERBOSE
            );
            throw IpCannotBeLocatedException::forEmptyAddress();
        }

        $ipAddr = $visit->getRemoteAddr();
        $this->io->write(sprintf('Processing IP <fg=blue>%s</>', $ipAddr));
        if ($ipAddr === IpAddress::LOCALHOST) {
            $this->io->writeln(' [<comment>Ignored localhost address</comment>]');
            throw IpCannotBeLocatedException::forLocalhost();
        }

        try {
            return $this->ipLocationResolver->resolveIpLocation($ipAddr);
        } catch (WrongIpException $e) {
            $this->io->writeln(' [<fg=red>An error occurred while locating IP. Skipped</>]');
            if ($this->io->isVerbose()) {
                $this->getApplication()->renderException($e, $this->io);
            }

            throw IpCannotBeLocatedException::forError($e);
        }
    }

    private function checkDbUpdate(): void
    {
        try {
            $this->dbUpdater->checkDbUpdate(function (bool $olderDbExists) {
                $this->io->writeln(
                    sprintf('<fg=blue>%s GeoLite2 database...</>', $olderDbExists ? 'Updating' : 'Downloading')
                );
                $this->progressBar = new ProgressBar($this->io);
            }, function (int $total, int $downloaded) {
                $this->progressBar->setMaxSteps($total);
                $this->progressBar->setProgress($downloaded);
            });

            if ($this->progressBar !== null) {
                $this->progressBar->finish();
                $this->io->newLine();
            }
        } catch (GeolocationDbUpdateFailedException $e) {
            if (! $e->olderDbExists()) {
                $this->io->error('GeoLite2 database download failed. It is not possible to locate visits.');
                throw $e;
            }

            $this->io->newLine();
            $this->io->writeln(
                '<fg=yellow;options=bold>[Warning] GeoLite2 database update failed. Proceeding with old version.</>'
            );
        }
    }
}
