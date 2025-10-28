<?php

namespace Shlinkio\Shlink\Core\Visit\Model;

use Shlinkio\Shlink\Common\Util\DateRange;

class WithDomainVisitsParams extends VisitsParams
{
    public function __construct(
        DateRange|null $dateRange = null,
        int|null $page = null,
        int|null $itemsPerPage = null,
        bool $excludeBots = false,
        public readonly string|null $domain = null,
    ) {
        parent::__construct($dateRange, $page, $itemsPerPage, $excludeBots);
    }

    public static function fromRawData(array $query): self
    {
        $visitsParams = VisitsParams::fromRawData($query);

        return new self(
            dateRange: $visitsParams->dateRange,
            page: $visitsParams->page,
            itemsPerPage: $visitsParams->itemsPerPage,
            excludeBots: $visitsParams->excludeBots,
            domain: $query['domain'] ?? null,
        );
    }
}
