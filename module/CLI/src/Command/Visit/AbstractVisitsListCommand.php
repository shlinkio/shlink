<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Command\Util\AbstractWithDateRangeCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function Functional\map;
use function Functional\select_keys;
use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\camelCaseToHumanFriendly;
use function sprintf;

abstract class AbstractVisitsListCommand extends AbstractWithDateRangeCommand
{
    public function __construct(protected readonly VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
    }

    final protected function getStartDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter visits, returning only those older than "%s".', $optionName);
    }

    final protected function getEndDateDesc(string $optionName): string
    {
        return sprintf('Allows to filter visits, returning only those newer than "%s".', $optionName);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $startDate = $this->getStartDateOption($input, $output);
        $endDate = $this->getEndDateOption($input, $output);
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
