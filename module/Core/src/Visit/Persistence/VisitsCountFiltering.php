<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsCountFiltering
{
    public function __construct(
        public readonly ?DateRange $dateRange = null,
        public readonly bool $excludeBots = false,
        public readonly ?ApiKey $apiKey = null,
    ) {
    }

    public static function withApiKey(?ApiKey $apiKey): self
    {
        return new self(apiKey: $apiKey);
    }
}
