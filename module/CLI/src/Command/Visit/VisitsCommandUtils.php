<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Input\VisitsListFormat;
use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function array_map;
use function Shlinkio\Shlink\Core\ArrayUtils\select_keys;
use function Shlinkio\Shlink\Core\camelCaseToHumanFriendly;

class VisitsCommandUtils
{
    /**
     * @param Paginator<Visit> $paginator
     * @param null|callable(Visit $visits): array<string, string> $mapExtraFields
     */
    public static function renderOutput(
        OutputInterface $output,
        VisitsListInput $inputData,
        Paginator $paginator,
        callable|null $mapExtraFields = null,
    ): void {
        if ($inputData->format !== VisitsListFormat::FULL) {
            // Avoid running out of memory by loading visits in chunks
            $paginator->setMaxPerPage(1000);
        }

        match ($inputData->format) {
            VisitsListFormat::CSV => self::renderCSVOutput($output, $paginator, $mapExtraFields),
            default => self::renderHumanFriendlyOutput($output, $paginator, $mapExtraFields),
        };
    }

    /**
     * @param Paginator<Visit> $paginator
     * @param null|callable(Visit $visits): array<string, string> $mapExtraFields
     */
    private static function renderCSVOutput(
        OutputInterface $output,
        Paginator $paginator,
        callable|null $mapExtraFields,
    ): void {
        // TODO
    }

    /**
     * @param Paginator<Visit> $paginator
     * @param null|callable(Visit $visits): array<string, string> $mapExtraFields
     */
    private static function renderHumanFriendlyOutput(
        OutputInterface $output,
        Paginator $paginator,
        callable|null $mapExtraFields,
    ): void {
        $page = 1;
        do {
            $paginator->setCurrentPage($page);
            $page++;

            [$rows, $headers] = self::resolveRowsAndHeaders($paginator, $mapExtraFields);
            ShlinkTable::default($output)->render(
                $headers,
                $rows,
                footerTitle: PagerfantaUtils::formatCurrentPageMessage($paginator, 'Page %s of %s'),
            );
        } while ($paginator->hasNextPage());
    }

    /**
     * @param Paginator<Visit> $paginator
     * @param null|callable(Visit $visits): array<string, string> $mapExtraFields
     */
    private static function resolveRowsAndHeaders(Paginator $paginator, callable|null $mapExtraFields): array
    {
        $extraKeys = null;
        $mapExtraFields ??= static fn (Visit $_) => [];

        $rows = array_map(function (Visit $visit) use (&$extraKeys, $mapExtraFields) {
            $extraFields = $mapExtraFields($visit);
            $extraKeys ??= array_keys($extraFields);

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
        $extra = array_map(camelCaseToHumanFriendly(...), $extraKeys ?? []);

        return [
            $rows,
            ['Referer', 'Date', 'User agent', 'Country', 'City', ...$extra],
        ];
    }
}
