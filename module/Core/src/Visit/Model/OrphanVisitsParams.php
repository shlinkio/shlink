<?php

namespace Shlinkio\Shlink\Core\Visit\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use ValueError;

use function implode;
use function Shlinkio\Shlink\Core\enumValues;
use function sprintf;

final class OrphanVisitsParams extends VisitsParams
{
    public function __construct(
        ?DateRange $dateRange = null,
        ?int $page = null,
        ?int $itemsPerPage = null,
        bool $excludeBots = false,
        public readonly ?OrphanVisitType $type = null,
    ) {
        parent::__construct($dateRange, $page, $itemsPerPage, $excludeBots);
    }

    public static function fromRawData(array $query): self
    {
        $visitsParams = parent::fromRawData($query);
        $type = $query['type'] ?? null;

        return new self(
            dateRange: $visitsParams->dateRange,
            page: $visitsParams->page,
            itemsPerPage: $visitsParams->itemsPerPage,
            excludeBots: $visitsParams->excludeBots,
            type: $type !== null ? self::parseType($type) : null,
        );
    }

    private static function parseType(string $type): OrphanVisitType
    {
        try {
            return OrphanVisitType::from($type);
        } catch (ValueError) {
            throw ValidationException::fromArray([
                'type' => sprintf(
                    '%s is not a valid orphan visit type. Expected one of ["%s"]',
                    $type,
                    implode('", "', enumValues(OrphanVisitType::class)),
                ),
            ]);
        }
    }
}
