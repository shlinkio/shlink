<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Model\DeviceLongUrlPair;

class DeviceLongUrl extends AbstractEntity
{
    private ShortUrl $shortUrl; // @phpstan-ignore-line

    private function __construct(
        public readonly DeviceType $deviceType,
        private string $longUrl,
    ) {
    }

    public static function fromPair(DeviceLongUrlPair $pair): self
    {
        return new self($pair->deviceType, $pair->longUrl);
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
