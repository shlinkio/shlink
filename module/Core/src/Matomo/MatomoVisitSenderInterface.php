<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface MatomoVisitSenderInterface
{
    public function sendVisitToMatomo(Visit $visit, ?string $originalIpAddress = null): void;
}
