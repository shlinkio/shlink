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
        GeolocationDownloadProgressHandlerInterface|null $downloadProgressHandler = null,
    ): GeolocationResult;
}
