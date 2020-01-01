<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Exception;
use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

use function sprintf;

class LocateVisitsCommand extends AbstractLockedCommand
{
    public const NAME = 'visit:locate';

    private VisitServiceInterface $visitService;
    private IpLocationResolverInterface $ipLocationResolver;
    private GeolocationDbUpdaterInterface $dbUpdater;

    private SymfonyStyle $io;
    private ?ProgressBar $progressBar = null;

    public function __construct(
        VisitServiceInterface $visitService,
        IpLocationResolverInterface $ipLocationResolver,
        LockFactory $locker,
        GeolocationDbUpdaterInterface $dbUpdater
    ) {
        parent::__construct($locker);
        $this->visitService = $visitService;
        $this->ipLocationResolver = $ipLocationResolver;
        $this->dbUpdater = $dbUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Resolves visits origin locations.');
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->checkDbUpdate();

            $this->visitService->locateUnlocatedVisits(
                [$this, 'getGeolocationDataForVisit'],
                static function (VisitLocation $location) use ($output): void {
                    if (!$location->isEmpty()) {
                        $output->writeln(
                            sprintf(' [<info>Address located at "%s"</info>]', $location->getCountryName()),
                        );
                    }
                },
            );

            $this->io->success('Finished processing all IPs');
            return ExitCodes::EXIT_SUCCESS;
        } catch (Throwable $e) {
            $this->io->error($e->getMessage());
            if ($e instanceof Exception && $this->io->isVerbose()) {
                $this->getApplication()->renderThrowable($e, $this->io);
            }

            return ExitCodes::EXIT_FAILURE;
        }
    }

    public function getGeolocationDataForVisit(Visit $visit): Location
    {
        if (! $visit->hasRemoteAddr()) {
            $this->io->writeln(
                '<comment>Ignored visit with no IP address</comment>',
                OutputInterface::VERBOSITY_VERBOSE,
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
                $this->getApplication()->renderThrowable($e, $this->io);
            }

            throw IpCannotBeLocatedException::forError($e);
        }
    }

    private function checkDbUpdate(): void
    {
        try {
            $this->dbUpdater->checkDbUpdate(function (bool $olderDbExists): void {
                $this->io->writeln(
                    sprintf('<fg=blue>%s GeoLite2 database...</>', $olderDbExists ? 'Updating' : 'Downloading'),
                );
                $this->progressBar = new ProgressBar($this->io);
            }, function (int $total, int $downloaded): void {
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
                '<fg=yellow;options=bold>[Warning] GeoLite2 database update failed. Proceeding with old version.</>',
            );
        }
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return new LockedCommandConfig($this->getName());
    }
}
