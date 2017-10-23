<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitServiceInterface
{
    /**
     * @return Visit[]
     */
    public function getUnlocatedVisits();

    /**
     * @param Visit $visit
     */
    public function saveVisit(Visit $visit);
}
