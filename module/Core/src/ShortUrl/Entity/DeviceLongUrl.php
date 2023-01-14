<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;

class DeviceLongUrl extends AbstractEntity
{
    public function __construct(
        public readonly ShortUrl $shortUrl,
        public readonly DeviceType $deviceType,
        private string $longUrl,
    ) {
    }

    public function longUrl(): string
    {
        return $this->longUrl;
    }

    public function updateLongUrl(string $longUrl): void
    {
        $this->longUrl = $longUrl;
    }
}
