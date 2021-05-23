<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;

interface GeolocationDbUpdaterInterface
{
    /**
     * @throws GeolocationDbUpdateFailedException
     */
    public function checkDbUpdate(?callable $beforeDownload = null, ?callable $handleProgress = null): void;
}
