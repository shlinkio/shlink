<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsCountFiltering
{
    public function __construct(
        private ?DateRange $dateRange = null,
        private bool $excludeBots = false,
        private ?ApiKey $apiKey = null,
    ) {
    }

    public static function withApiKey(?ApiKey $apiKey): self
    {
        return new self(null, false, $apiKey);
    }

    public function dateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function excludeBots(): bool
    {
        return $this->excludeBots;
    }

    public function apiKey(): ?ApiKey
    {
        return $this->apiKey;
    }
}
