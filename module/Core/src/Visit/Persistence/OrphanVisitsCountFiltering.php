<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class OrphanVisitsCountFiltering extends VisitsCountFiltering
{
    public function __construct(
        DateRange|null $dateRange = null,
        bool $excludeBots = false,
        ApiKey|null $apiKey = null,
        public readonly OrphanVisitType|null $type = null,
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey);
    }
}
