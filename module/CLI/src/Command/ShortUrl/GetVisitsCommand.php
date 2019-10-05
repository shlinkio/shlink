<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\Stdlib\ArrayUtils;

use function array_map;
use function Functional\select_keys;

class GetVisitsCommand extends Command
{
    public const NAME = 'short-url:visits';
    private const ALIASES = ['shortcode:visits', 'short-code:visits'];

    /** @var VisitsTrackerInterface */
    private $visitsTracker;

    public function __construct(VisitsTrackerInterface $visitsTracker)
    {
        $this->visitsTracker = $visitsTracker;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Returns the detailed visits information for provided short code')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code which visits we want to get')
            ->addOption(
                'startDate',
                's',
                InputOption::VALUE_OPTIONAL,
                'Allows to filter visits, returning only those older than start date'
            )
            ->addOption(
                'endDate',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Allows to filter visits, returning only those newer than end date'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask('A short code was not provided. Which short code do you want to use?');
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $shortCode = $input->getArgument('shortCode');
        $startDate = $this->getDateOption($input, 'startDate');
        $endDate = $this->getDateOption($input, 'endDate');

        $paginator = $this->visitsTracker->info($shortCode, new VisitsParams(new DateRange($startDate, $endDate)));
        $visits = ArrayUtils::iteratorToArray($paginator->getCurrentItems());

        $rows = array_map(function (Visit $visit) {
            $rowData = $visit->jsonSerialize();
            $rowData['country'] = $visit->getVisitLocation()->getCountryName();
            return select_keys($rowData, ['referer', 'date', 'userAgent', 'country']);
        }, $visits);
        ShlinkTable::fromOutput($output)->render(['Referer', 'Date', 'User agent', 'Country'], $rows);
        return ExitCodes::EXIT_SUCCESS;
    }

    private function getDateOption(InputInterface $input, $key)
    {
        $value = $input->getOption($key);
        return ! empty($value) ? Chronos::parse($value) : $value;
    }
}
