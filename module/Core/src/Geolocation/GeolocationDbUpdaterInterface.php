<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;

interface GeolocationDbUpdaterInterface
{
    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(
        callable|null $beforeDownload = null,
        callable|null $handleProgress = null,
    ): GeolocationResult;
}
