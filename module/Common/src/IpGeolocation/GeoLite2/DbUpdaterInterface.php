<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation\GeoLite2;

use Shlinkio\Shlink\Common\Exception\RuntimeException;

interface DbUpdaterInterface
{
    /**
     * @throws RuntimeException
     */
    public function downloadFreshCopy(callable $handleProgress = null): void;
}
