<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\Service\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function sleep;
use function sprintf;

class ProcessVisitsCommand extends Command
{
    public const NAME = 'visit:process';

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
        parent::__construct(null);
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

        $count = 0;
        foreach ($visits as $visit) {
            if (! $visit->hasRemoteAddr()) {
                $io->writeln(
                    sprintf('<comment>%s</comment>', $this->translator->translate('Ignored visit with no IP address')),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                continue;
            }

            $ipAddr = $visit->getRemoteAddr();
            $io->write(sprintf('%s <info>%s</info>', $this->translator->translate('Processing IP'), $ipAddr));
            if ($ipAddr === IpAddress::LOCALHOST) {
                $io->writeln(
                    sprintf(' (<comment>%s</comment>)', $this->translator->translate('Ignored localhost address'))
                );
                continue;
            }

            $count++;
            try {
                $result = $this->ipLocationResolver->resolveIpLocation($ipAddr);

                $location = new VisitLocation($result);
                $visit->setVisitLocation($location);
                $this->visitService->saveVisit($visit);

                $io->writeln(sprintf(
                    ' (' . $this->translator->translate('Address located at "%s"') . ')',
                    $location->getCityName()
                ));
            } catch (WrongIpException $e) {
                $io->writeln(
                    sprintf(' <error>%s</error>', $this->translator->translate('An error occurred while locating IP'))
                );
                if ($io->isVerbose()) {
                    $this->getApplication()->renderException($e, $output);
                }
            }

            if ($count === $this->ipLocationResolver->getApiLimit()) {
                $count = 0;
                $seconds = $this->ipLocationResolver->getApiInterval();
                $io->note(sprintf(
                    $this->translator->translate('IP location resolver limit reached. Waiting %s seconds...'),
                    $seconds
                ));
                sleep($seconds);
            }
        }

        $io->success($this->translator->translate('Finished processing all IPs'));
    }
}
