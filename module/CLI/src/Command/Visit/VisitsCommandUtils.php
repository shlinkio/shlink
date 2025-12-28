<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

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
    public static function resolveRowsAndHeaders(Paginator $paginator, callable|null $mapExtraFields = null): array
    {
        $extraKeys = [];
        $mapExtraFields ??= static fn (Visit $_) => [];

        $rows = array_map(function (Visit $visit) use (&$extraKeys, $mapExtraFields) {
            $extraFields = $mapExtraFields($visit);
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
}
