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

use function array_keys;
use function array_map;
use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\ArrayUtils\select_keys;
use function Shlinkio\Shlink\Core\camelCaseToHumanFriendly;

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
        [$rows, $headers] = $this->resolveRowsAndHeaders($paginator);

        ShlinkTable::default($output)->render($headers, $rows);

        return self::SUCCESS;
    }

    /**
     * @param Paginator<Visit> $paginator
     */
    private function resolveRowsAndHeaders(Paginator $paginator): array
    {
        $extraKeys = [];
        $rows = array_map(function (Visit $visit) use (&$extraKeys) {
            $extraFields = $this->mapExtraFields($visit);
            $extraKeys = array_keys($extraFields);

            $rowData = [
                'referer' => $visit->referer,
                'date' => $visit->date->toAtomString(),
                'userAgent' => $visit->userAgent,
                'potentialBot' => $visit->potentialBot,
                'country' => $visit->getVisitLocation()->countryName ?? 'Unknown',
                'city' => $visit->getVisitLocation()->cityName ?? 'Unknown',
                ...$extraFields,
            ];

            // Filter out unknown keys
            return select_keys($rowData, ['referer', 'date', 'userAgent', 'country', 'city', ...$extraKeys]);
        }, [...$paginator->getCurrentPageResults()]);
        $extra = array_map(camelCaseToHumanFriendly(...), $extraKeys);

        return [
            $rows,
            ['Referer', 'Date', 'User agent', 'Country', 'City', ...$extra],
        ];
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
