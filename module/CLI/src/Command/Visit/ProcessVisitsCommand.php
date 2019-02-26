<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory as Locker;
use function sprintf;

class ProcessVisitsCommand extends Command
{
    public const NAME = 'visit:process';

    /** @var VisitServiceInterface */
    private $visitService;
    /** @var IpLocationResolverInterface */
    private $ipLocationResolver;
    /** @var Locker */
    private $locker;
    /** @var OutputInterface */
    private $output;

    public function __construct(
        VisitServiceInterface $visitService,
        IpLocationResolverInterface $ipLocationResolver,
        Locker $locker
    ) {
        parent::__construct();
        $this->visitService = $visitService;
        $this->ipLocationResolver = $ipLocationResolver;
        $this->locker = $locker;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Processes visits where location is not set yet');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->output = $output;
        $io = new SymfonyStyle($input, $output);

        $lock = $this->locker->createLock(self::NAME);
        if (! $lock->acquire()) {
            $io->warning(sprintf('There is already an instance of the "%s" command in execution', self::NAME));
            return ExitCodes::EXIT_WARNING;
        }

        try {
            $this->visitService->locateUnlocatedVisits(
                [$this, 'getGeolocationDataForVisit'],
                function (VisitLocation $location) use ($output) {
                    $output->writeln(sprintf(' [<info>Address located at "%s"</info>]', $location->getCountryName()));
                }
            );

            $io->success('Finished processing all IPs');
        } finally {
            $lock->release();
            return ExitCodes::EXIT_SUCCESS;
        }
    }

    public function getGeolocationDataForVisit(Visit $visit): Location
    {
        if (! $visit->hasRemoteAddr()) {
            $this->output->writeln(
                '<comment>Ignored visit with no IP address</comment>',
                OutputInterface::VERBOSITY_VERBOSE
            );
            throw IpCannotBeLocatedException::forEmptyAddress();
        }

        $ipAddr = $visit->getRemoteAddr();
        $this->output->write(sprintf('Processing IP <fg=blue>%s</>', $ipAddr));
        if ($ipAddr === IpAddress::LOCALHOST) {
            $this->output->writeln(' [<comment>Ignored localhost address</comment>]');
            throw IpCannotBeLocatedException::forLocalhost();
        }

        try {
            return $this->ipLocationResolver->resolveIpLocation($ipAddr);
        } catch (WrongIpException $e) {
            $this->output->writeln(' [<fg=red>An error occurred while locating IP. Skipped</>]');
            if ($this->output->isVerbose()) {
                $this->getApplication()->renderException($e, $this->output);
            }

            throw IpCannotBeLocatedException::forError($e);
        }
    }
}
