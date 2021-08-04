<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;

class VisitsCountFiltering
{
    public function __construct(
        private ?DateRange $dateRange = null,
        private bool $excludeBots = false,
        private ?Specification $spec = null
    ) {
    }

    public function dateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function excludeBots(): bool
    {
        return $this->excludeBots;
    }

    public function spec(): ?Specification
    {
        return $this->spec;
    }
}
