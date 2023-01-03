<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;

class DeviceLongUrl extends AbstractEntity
{
    private function __construct(
        public readonly ShortUrl $shortUrl,
        public readonly DeviceType $deviceType,
        public readonly string $longUrl,
    ) {
    }
}
