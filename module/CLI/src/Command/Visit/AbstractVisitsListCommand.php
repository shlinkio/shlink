<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Input\EndDateOption;
use Shlinkio\Shlink\CLI\Input\StartDateOption;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Shlinkio\Shlink\Common\buildDateRange;

abstract class AbstractVisitsListCommand extends Command
{
    private readonly StartDateOption $startDateOption;
    private readonly EndDateOption $endDateOption;

    public function __construct(protected readonly VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
        $this->startDateOption = new StartDateOption($this, 'visits');
        $this->endDateOption = new EndDateOption($this, 'visits');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startDate = $this->startDateOption->get($input, $output);
        $endDate = $this->endDateOption->get($input, $output);
        $paginator = $this->getVisitsPaginator($input, buildDateRange($startDate, $endDate));
        [$rows, $headers] = VisitsCommandUtils::resolveRowsAndHeaders($paginator, $this->mapExtraFields(...));

        ShlinkTable::default($output)->render($headers, $rows);

        return self::SUCCESS;
    }

    /**
     * @return Paginator<Visit>
     */
    abstract protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator;

    /**
     * @return array<string, string>
     */
    abstract protected function mapExtraFields(Visit $visit): array;
}
