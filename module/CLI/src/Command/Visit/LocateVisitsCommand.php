<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\VisitLocatorInterface;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

use function sprintf;

class LocateVisitsCommand extends AbstractLockedCommand implements VisitGeolocationHelperInterface
{
    public const NAME = 'visit:locate';

    private VisitLocatorInterface $visitLocator;
    private IpLocationResolverInterface $ipLocationResolver;
    private GeolocationDbUpdaterInterface $dbUpdater;

    private SymfonyStyle $io;
    private ?ProgressBar $progressBar = null;

    public function __construct(
        VisitLocatorInterface $visitLocator,
        IpLocationResolverInterface $ipLocationResolver,
        LockFactory $locker,
        GeolocationDbUpdaterInterface $dbUpdater
    ) {
        parent::__construct($locker);
        $this->visitLocator = $visitLocator;
        $this->ipLocationResolver = $ipLocationResolver;
        $this->dbUpdater = $dbUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Resolves visits origin locations.')
            ->addOption(
                'retry',
                'r',
                InputOption::VALUE_NONE,
                'Will retry the location of visits that were located with a not-found location, in case it was due to '
                . 'a temporal issue.',
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'When provided together with --retry, will locate all existing visits, regardless the fact that they '
                . 'have already been located.',
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $retry = $input->getOption('retry');
        $all = $input->getOption('all');

        if ($all && !$retry) {
            $this->io->writeln(
                '<comment>The <fg=yellow;options=bold>--all</> flag has no effect on its own. You have to provide it '
                . 'together with <fg=yellow;options=bold>--retry</>.</comment>',
            );
        }

        if ($all && $retry && ! $this->warnAndVerifyContinue()) {
            throw new RuntimeException('Execution aborted');
        }
    }

    private function warnAndVerifyContinue(): bool
    {
        $this->io->warning([
            'You are about to process the location of all existing visits your short URLs received.',
            'Since shlink saves visitors IP addresses anonymized, you could end up losing precision on some of '
            . 'your visits.',
            'Also, if you have a large amount of visits, this can be a very time consuming process. '
            . 'Continue at your own risk.',
        ]);
        return $this->io->confirm('Do you want to proceed?', false);
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $retry = $input->getOption('retry');
        $all = $retry && $input->getOption('all');

        try {
            $this->checkDbUpdate();

            if ($all) {
                $this->visitLocator->locateAllVisits($this);
            } else {
                $this->visitLocator->locateUnlocatedVisits($this);
                if ($retry) {
                    $this->visitLocator->locateVisitsWithEmptyLocation($this);
                }
            }

            $this->io->success('Finished locating visits');
            return ExitCodes::EXIT_SUCCESS;
        } catch (Throwable $e) {
            $this->io->error($e->getMessage());
            if ($e instanceof Throwable && $this->io->isVerbose()) {
                $this->getApplication()->renderThrowable($e, $this->io);
            }

            return ExitCodes::EXIT_FAILURE;
        }
    }

    /**
     * @throws IpCannotBeLocatedException
     */
    public function geolocateVisit(Visit $visit): Location
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

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
    {
        $message = ! $visitLocation->isEmpty()
            ? sprintf(' [<info>Address located in "%s"</info>]', $visitLocation->getCountryName())
            : ' [<comment>Address not found</comment>]';
        $this->io->writeln($message);
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
