<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;

interface VisitServiceInterface
{
    /**
     * @return Visit[]
     */
    public function getUnlocatedVisits(): array;

    public function locateVisit(Visit $visit, VisitLocation $location, bool $clear = false): void;
}
