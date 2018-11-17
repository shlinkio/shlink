<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function sprintf;

class ProcessVisitsCommand extends Command
{
    public const NAME = 'visit:process';
    private const CLEAR_INTERVAL = 100;

    /**
     * @var VisitServiceInterface
     */
    private $visitService;
    /**
     * @var IpLocationResolverInterface
     */
    private $ipLocationResolver;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        VisitServiceInterface $visitService,
        IpLocationResolverInterface $ipLocationResolver,
        TranslatorInterface $translator
    ) {
        $this->visitService = $visitService;
        $this->ipLocationResolver = $ipLocationResolver;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
             ->setDescription(
                 $this->translator->translate('Processes visits where location is not set yet')
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $visits = $this->visitService->getUnlocatedVisits();

        foreach ($visits as $i => $visit) {
            $clear = ($i % self::CLEAR_INTERVAL) === 0;
            $this->processVisit($output, $visit, $clear);
        }

        $io->success($this->translator->translate('Finished processing all IPs'));
    }

    private function processVisit(OutputInterface $output, Visit $visit, bool $clear): void
    {
        if (! $visit->hasRemoteAddr()) {
            $output->writeln(
                sprintf('<comment>%s</comment>', $this->translator->translate('Ignored visit with no IP address')),
                OutputInterface::VERBOSITY_VERBOSE
            );
            return;
        }

        $ipAddr = $visit->getRemoteAddr();
        $output->write(sprintf('%s <fg=blue>%s</>', $this->translator->translate('Processing IP'), $ipAddr));
        if ($ipAddr === IpAddress::LOCALHOST) {
            $output->writeln(
                sprintf(' [<comment>%s</comment>]', $this->translator->translate('Ignored localhost address'))
            );
            return;
        }

        try {
            $result = $this->ipLocationResolver->resolveIpLocation($ipAddr);
        } catch (WrongIpException $e) {
            $output->writeln(
                sprintf(
                    ' [<fg=red>%s</>]',
                    $this->translator->translate('An error occurred while locating IP. Skipped')
                )
            );
            if ($output->isVerbose()) {
                $this->getApplication()->renderException($e, $output);
            }

            return;
        }

        $location = new VisitLocation($result);
        $this->visitService->locateVisit($visit, $location, $clear);
        $output->writeln(sprintf(
            ' [<info>' . $this->translator->translate('Address located at "%s"') . '</info>]',
            $location->getCountryName()
        ));
    }
}
