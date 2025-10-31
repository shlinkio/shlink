<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class OrphanVisitsCountFiltering extends WithDomainVisitsCountFiltering
{
    public function __construct(
        DateRange|null $dateRange = null,
        bool $excludeBots = false,
        ApiKey|null $apiKey = null,
        string|null $domain = null,
        public readonly OrphanVisitType|null $type = null,
        public readonly string $defaultDomain = '',
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey, $domain);
    }
}
