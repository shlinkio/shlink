<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class OrphanVisitsListFiltering extends OrphanVisitsCountFiltering
{
    public function __construct(
        ?DateRange $dateRange = null,
        bool $excludeBots = false,
        ?ApiKey $apiKey = null,
        ?OrphanVisitType $type = null,
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey, $type);
    }
}
