<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class OrphanVisitsListFiltering extends OrphanVisitsCountFiltering
{
    public function __construct(
        DateRange|null $dateRange = null,
        bool $excludeBots = false,
        ApiKey|null $apiKey = null,
        string|null $domain = null,
        OrphanVisitType|null $type = null,
        public readonly int|null $limit = null,
        public readonly int|null $offset = null,
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey, $domain, $type);
    }
}
