<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

interface VisitServiceInterface
{
    public function locateUnlocatedVisits(callable $geolocateVisit, ?callable $notifyVisitWithLocation = null): void;
}
