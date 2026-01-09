<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use League\Csv\Writer;
use Shlinkio\Shlink\CLI\Input\VisitsListFormat;
use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;

class VisitsCommandUtils
{
    /**
     * @param Paginator<Visit> $paginator
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
            VisitsListFormat::CSV => self::renderCSVOutput($output, $paginator),
            default => self::renderHumanFriendlyOutput($output, $paginator),
        };
    }

    /**
     * @param Paginator<Visit> $paginator
     */
    private static function renderCSVOutput(OutputInterface $output, Paginator $paginator): void
    {
        $page = 1;
        do {
            $paginator->setCurrentPage($page);

            [$rows, $headers] = self::resolveRowsAndHeaders($paginator);
            $csv = Writer::fromString();
            if ($page === 1) {
                $csv->insertOne($headers);
            }

            $csv->insertAll($rows);
            $output->write($csv->toString());

            $page++;
        } while ($paginator->hasNextPage());
    }

    /**
     * @param Paginator<Visit> $paginator
     */
    private static function renderHumanFriendlyOutput(OutputInterface $output, Paginator $paginator): void
    {
        $page = 1;
        do {
            $paginator->setCurrentPage($page);
            $page++;

            [$rows, $headers] = self::resolveRowsAndHeaders($paginator);
            ShlinkTable::default($output)->render(
                $headers,
                $rows,
                footerTitle: PagerfantaUtils::formatCurrentPageMessage($paginator, 'Page %s of %s'),
            );
        } while ($paginator->hasNextPage());
    }

    /**
     * @param Paginator<Visit> $paginator
     */
    private static function resolveRowsAndHeaders(Paginator $paginator): array
    {
        $headers = [
            'Date',
            'Potential bot',
            'User agent',
            'Referer',
            'Country',
            'Region',
            'City',
            'Visited URL',
            'Redirect URL',
            'Type',
        ];
        $rows = array_map(function (Visit $visit) {
            $visitLocation = $visit->visitLocation;

            return [
                'date' => $visit->date->toAtomString(),
                'potentialBot' => $visit->potentialBot ? 'Potential bot' : '',
                'userAgent' => $visit->userAgent,
                'referer' => $visit->referer,
                'country' => $visitLocation->countryName ?? 'Unknown',
                'region' => $visitLocation->regionName ?? 'Unknown',
                'city' => $visitLocation->cityName ?? 'Unknown',
                'visitedUrl' => $visit->visitedUrl ?? 'Unknown',
                'redirectUrl' => $visit->redirectUrl ?? 'Unknown',
                'type' => $visit->type->value,
            ];
        }, [...$paginator->getCurrentPageResults()]);

        return [$rows, $headers];
    }
}
