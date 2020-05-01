<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;

interface VisitsStatsHelperInterface
{
    public function getVisitsStats(): VisitsStats;
}
