<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsCountFiltering
{
    public function __construct(
        public readonly DateRange|null $dateRange = null,
        public readonly bool $excludeBots = false,
        public readonly ApiKey|null $apiKey = null,
    ) {
    }
}
