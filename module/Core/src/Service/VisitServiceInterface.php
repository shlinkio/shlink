<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

interface VisitServiceInterface
{
    public function locateVisits(callable $getGeolocationData, ?callable $locatedVisit = null): void;
}
