<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Model\DeviceLongUrlPair;

class DeviceLongUrl extends AbstractEntity
{
    private function __construct(
        private readonly ShortUrl $shortUrl, // No need to read this field. It's used by doctrine
        public readonly DeviceType $deviceType,
        private string $longUrl,
    ) {
    }

    public static function fromShortUrlAndPair(ShortUrl $shortUrl, DeviceLongUrlPair $pair): self
    {
        return new self($shortUrl, $pair->deviceType, $pair->longUrl);
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
