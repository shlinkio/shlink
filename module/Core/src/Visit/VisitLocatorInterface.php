<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

interface VisitLocatorInterface
{
    public function locateUnlocatedVisits(VisitGeolocationHelperInterface $helper): void;

    public function locateVisitsWithEmptyLocation(VisitGeolocationHelperInterface $helper): void;

    public function locateAllVisits(VisitGeolocationHelperInterface $helper): void;
}
