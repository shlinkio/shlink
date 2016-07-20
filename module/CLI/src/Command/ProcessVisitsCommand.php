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
     * ProcessVisitsCommand constructor.
     * @param VisitServiceInterface|VisitService $visitService
     * @param IpLocationResolverInterface|IpLocationResolver $ipLocationResolver
     *
     * @Inject({VisitService::class, IpLocationResolver::class})
     */
    public function __construct(VisitServiceInterface $visitService, IpLocationResolverInterface $ipLocationResolver)
    {
        parent::__construct(null);
        $this->visitService = $visitService;
        $this->ipLocationResolver = $ipLocationResolver;
    }

    public function configure()
    {
        $this->setName('visit:process')
             ->setDescription('Processes visits where location is not set already');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $visits = $this->visitService->getUnlocatedVisits();

        foreach ($visits as $visit) {
            $ipAddr = $visit->getRemoteAddr();
            $output->write(sprintf('Processing IP <info>%s</info>', $ipAddr));
            if ($ipAddr === self::LOCALHOST) {
                $output->writeln(' (<comment>Ignored localhost address</comment>)');
                continue;
            }

            try {
                $result = $this->ipLocationResolver->resolveIpLocation($ipAddr);
                $location = new VisitLocation();
                $location->exchangeArray($result);
                $visit->setVisitLocation($location);
                $this->visitService->saveVisit($visit);
                $output->writeln(sprintf(' (Address located at "%s")', $location->getCityName()));
            } catch (WrongIpException $e) {
                continue;
            }
        }

        $output->writeln('Finished processing all IPs');
    }
}
