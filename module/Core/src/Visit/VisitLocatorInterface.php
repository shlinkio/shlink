<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

interface VisitLocatorInterface
{
    public function locateUnlocatedVisits(callable $geolocateVisit, callable $notifyVisitWithLocation): void;

    public function locateVisitsWithEmptyLocation(callable $geolocateVisit, callable $notifyVisitWithLocation): void;
}
