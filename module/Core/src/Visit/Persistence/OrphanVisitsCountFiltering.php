<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class OrphanVisitsCountFiltering extends VisitsCountFiltering
{
    public function __construct(
        ?DateRange $dateRange = null,
        bool $excludeBots = false,
        ?ApiKey $apiKey = null,
        public readonly ?OrphanVisitType $type = null,
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey);
    }
}
