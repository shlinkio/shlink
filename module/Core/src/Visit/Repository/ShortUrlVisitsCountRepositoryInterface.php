<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;

interface ShortUrlVisitsCountRepositoryInterface
{
    public function countNonOrphanVisits(VisitsCountFiltering $filtering): int;
}
