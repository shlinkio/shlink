<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo\Model;

use Countable;

final readonly class SendVisitsResult implements Countable
{
    /**
     * @param int<0, max> $successfulVisits
     * @param int<0, max> $failedVisits
     */
    public function __construct(public int $successfulVisits = 0, public int $failedVisits = 0)
    {
    }

    public function hasSuccesses(): bool
    {
        return $this->successfulVisits > 0;
    }

    public function hasFailures(): bool
    {
        return $this->failedVisits > 0;
    }

    public function count(): int
    {
        return $this->successfulVisits + $this->failedVisits;
    }
}
