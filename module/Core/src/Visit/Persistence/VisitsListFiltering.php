<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class VisitsListFiltering extends VisitsCountFiltering
{
    public function __construct(
        ?DateRange $dateRange = null,
        bool $excludeBots = false,
        ?ApiKey $apiKey = null,
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
        parent::__construct($dateRange, $excludeBots, $apiKey);
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function offset(): ?int
    {
        return $this->offset;
    }
}
