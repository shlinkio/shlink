<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Command\Util\CommandUtils;
use Shlinkio\Shlink\CLI\Command\Util\LockConfig;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocatorInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Model\UnlocatableIpType;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

use function sprintf;

#[AsCommand(
    name: LocateVisitsCommand::NAME,
    description: 'Resolves visits origin locations. It implicitly downloads/updates the GeoLite2 db file if needed',
)]
class LocateVisitsCommand extends Command implements VisitGeolocationHelperInterface
{
    public const string NAME = 'visit:locate';

    private SymfonyStyle $io;

    public function __construct(
        private readonly VisitLocatorInterface $visitLocator,
        private readonly VisitToLocationHelperInterface $visitToLocation,
        private readonly LockFactory $locker,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            'Will retry the location of visits that were located with a not-found location, in case it was due to '
            . 'a temporal issue.',
            shortcut: 'r',
        )]
        bool $retry = false,
        #[Option(
            'When provided together with --retry, will locate all existing visits, regardless the fact that they '
            . 'have already been located.',
            shortcut: 'a',
        )]
        bool $all = false,
    ): int {
        $this->io = $io;

        if ($all && !$retry) {
            $io->writeln(
                '<comment>The <fg=yellow;options=bold>--all</> flag has no effect on its own. You have to provide it '
                . 'together with <fg=yellow;options=bold>--retry</>.</comment>',
            );
        }

        if ($all && $retry && ! $this->warnAndVerifyContinue()) {
            throw new RuntimeException('Execution aborted');
        }

        return CommandUtils::executeWithLock(
            $this->locker,
            LockConfig::nonBlocking(self::NAME),
            $io,
            fn () => $this->locateVisits($retry, $all),
        );
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

    private function locateVisits(bool $retry, bool $all): int
    {
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
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->io->error($e->getMessage());
            if ($this->io->isVerbose()) {
                $this->getApplication()?->renderThrowable($e, $this->io);
            }

            return self::FAILURE;
        }
    }

    /**
     * @throws IpCannotBeLocatedException
     */
    public function geolocateVisit(Visit $visit): Location
    {
        $ipAddr = $visit->remoteAddr ?? '?';
        $this->io->write(sprintf('Processing IP <fg=blue>%s</>', $ipAddr));

        try {
            return $this->visitToLocation->resolveVisitLocation($visit);
        } catch (IpCannotBeLocatedException $e) {
            $this->io->writeln(match ($e->type) {
                UnlocatableIpType::EMPTY_ADDRESS => ' [<comment>Ignored visit with no IP address</comment>]',
                UnlocatableIpType::LOCALHOST => ' [<comment>Ignored localhost address</comment>]',
                UnlocatableIpType::ERROR => ' [<fg=red>An error occurred while locating IP. Skipped</>]',
            });

            if ($e->type === UnlocatableIpType::ERROR && $this->io->isVerbose()) {
                $this->getApplication()?->renderThrowable($e, $this->io);
            }

            throw $e;
        }
    }

    public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
    {
        if (! $visitLocation->isEmpty) {
            $this->io->writeln(sprintf(' [<info>Address located in "%s"</info>]', $visitLocation->countryName));
        } elseif ($visit->hasRemoteAddr() && $visit->remoteAddr !== IpAddress::LOCALHOST) {
            $this->io->writeln(' <comment>[Could not locate address]</comment>');
        }
    }

    private function checkDbUpdate(): void
    {
        $cliApp = $this->getApplication();
        if ($cliApp === null) {
            return;
        }

        $downloadDbCommand = $cliApp->find(DownloadGeoLiteDbCommand::NAME);
        $exitCode = $downloadDbCommand->run(new ArrayInput([]), $this->io);

        if ($exitCode === self::FAILURE) {
            throw new RuntimeException('It is not possible to locate visits without a GeoLite2 db file.');
        }
    }
}
