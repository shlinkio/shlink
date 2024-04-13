<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Throwable;

interface VisitSendingProgressTrackerInterface
{
    public function success(int $index): void;

    public function error(int $index, Throwable $e): void;
}
