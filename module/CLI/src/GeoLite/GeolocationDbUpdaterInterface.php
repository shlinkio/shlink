<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\GeoLite;

use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;

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
