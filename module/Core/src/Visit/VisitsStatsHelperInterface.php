<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface VisitsStatsHelperInterface
{
    public function getVisitsStats(?ApiKey $apiKey = null): VisitsStats;
}
