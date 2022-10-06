<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Option\EndDateOption;
use Shlinkio\Shlink\CLI\Option\StartDateOption;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function Functional\map;
use function Functional\select_keys;
use function Shlinkio\Shlink\Common\buildDateRange;
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

    final protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $startDate = $this->startDateOption->get($input, $output);
        $endDate = $this->endDateOption->get($input, $output);
        $paginator = $this->getVisitsPaginator($input, buildDateRange($startDate, $endDate));
        [$rows, $headers] = $this->resolveRowsAndHeaders($paginator);

        ShlinkTable::default($output)->render($headers, $rows);

        return ExitCodes::EXIT_SUCCESS;
    }

    private function resolveRowsAndHeaders(Paginator $paginator): array
    {
        $extraKeys = [];
        $rows = map($paginator->getCurrentPageResults(), function (Visit $visit) use (&$extraKeys) {
            $extraFields = $this->mapExtraFields($visit);
            $extraKeys = array_keys($extraFields);

            $rowData = [
                ...$visit->jsonSerialize(),
                'country' => $visit->getVisitLocation()?->getCountryName() ?? 'Unknown',
                'city' => $visit->getVisitLocation()?->getCityName() ?? 'Unknown',
                ...$extraFields,
            ];

            return select_keys($rowData, ['referer', 'date', 'userAgent', 'country', 'city', ...$extraKeys]);
        });
        $extra = map($extraKeys, camelCaseToHumanFriendly(...));

        return [
            $rows,
            ['Referer', 'Date', 'User agent', 'Country', 'City', ...$extra],
        ];
    }

    abstract protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator;

    /**
     * @return array<string, string>
     */
    abstract protected function mapExtraFields(Visit $visit): array;
}
