<?php
namespace Shlinkio\Shlink\CLI\Command;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\Service\IpLocationResolver;
use Shlinkio\Shlink\Common\Service\IpLocationResolverInterface;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Service\VisitService;
use Shlinkio\Shlink\Core\Service\VisitServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class ProcessVisitsCommand extends Command
{
    const LOCALHOST = '127.0.0.1';

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

    /**
     * ProcessVisitsCommand constructor.
     * @param VisitServiceInterface|VisitService $visitService
     * @param IpLocationResolverInterface|IpLocationResolver $ipLocationResolver
     * @param TranslatorInterface $translator
     *
     * @Inject({VisitService::class, IpLocationResolver::class, "translator"})
     */
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

    public function configure()
    {
        $this->setName('visit:process')
             ->setDescription(
                 $this->translator->translate('Processes visits where location is not set yet')
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $visits = $this->visitService->getUnlocatedVisits();

        foreach ($visits as $visit) {
            $ipAddr = $visit->getRemoteAddr();
            $output->write(sprintf('%s <info>%s</info>', $this->translator->translate('Processing IP'), $ipAddr));
            if ($ipAddr === self::LOCALHOST) {
                $output->writeln(
                    sprintf(' (<comment>%s</comment>)', $this->translator->translate('Ignored localhost address'))
                );
                continue;
            }

            try {
                $result = $this->ipLocationResolver->resolveIpLocation($ipAddr);
                $location = new VisitLocation();
                $location->exchangeArray($result);
                $visit->setVisitLocation($location);
                $this->visitService->saveVisit($visit);
                $output->writeln(sprintf(
                    ' (' . $this->translator->translate('Address located at "%s"') . ')',
                    $location->getCityName()
                ));
            } catch (WrongIpException $e) {
                continue;
            }
        }

        $output->writeln($this->translator->translate('Finished processing all IPs'));
    }
}
