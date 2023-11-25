<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use MatomoTracker;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

interface MatomoTrackerBuilderInterface
{
    /**
     * @throws RuntimeException If there's any missing matomo parameter
     */
    public function buildMatomoTracker(): MatomoTracker;
}
