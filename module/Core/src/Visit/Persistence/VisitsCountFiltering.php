<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Persistence;

use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;

class VisitsCountFiltering
{
    private ?DateRange $dateRange;
    private bool $excludeBots;
    private ?Specification $spec;

    public function __construct(?DateRange $dateRange = null, bool $excludeBots = false, ?Specification $spec = null)
    {
        $this->dateRange = $dateRange;
        $this->excludeBots = $excludeBots;
        $this->spec = $spec;
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
