<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Functional\map;
use function Functional\select_keys;

class GetVisitsCommand extends AbstractWithDateRangeCommand
{
    public const NAME = 'short-url:visits';

    private VisitsTrackerInterface $visitsTracker;

    public function __construct(VisitsTrackerInterface $visitsTracker)
    {
        $this->visitsTracker = $visitsTracker;
        parent::__construct();
    }

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the detailed visits information for provided short code')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code which visits we want to get');
    }

    protected function getStartDateDesc(): string
    {
        return 'Allows to filter visits, returning only those older than start date';
    }

    protected function getEndDateDesc(): string
    {
        return 'Allows to filter visits, returning only those newer than end date';
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
        $startDate = $this->getDateOption($input, $output, 'startDate');
        $endDate = $this->getDateOption($input, $output, 'endDate');

        $paginator = $this->visitsTracker->info($shortCode, new VisitsParams(new DateRange($startDate, $endDate)));

        $rows = map($paginator->getCurrentItems(), function (Visit $visit) {
            $rowData = $visit->jsonSerialize();
            $rowData['country'] = $visit->getVisitLocation()->getCountryName();
            return select_keys($rowData, ['referer', 'date', 'userAgent', 'country']);
        });
        ShlinkTable::fromOutput($output)->render(['Referer', 'Date', 'User agent', 'Country'], $rows);

        return ExitCodes::EXIT_SUCCESS;
    }
}
