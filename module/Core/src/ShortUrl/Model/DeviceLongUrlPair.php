<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\Model\DeviceType;

use function array_values;
use function Functional\map;
use function trim;

final class DeviceLongUrlPair
{
    private function __construct(public readonly DeviceType $deviceType, public readonly string $longUrl)
    {
    }

    public static function fromRawTypeAndLongUrl(string $type, string $longUrl): self
    {
        return new self(DeviceType::from($type), trim($longUrl));
    }

    /**
     * @param array<string, string> $map
     * @return self[]
     */
    public static function fromMapToList(array $map): array
    {
        return array_values(map(
            $map,
            fn (string $longUrl, string $deviceType) => self::fromRawTypeAndLongUrl($deviceType, $longUrl),
        ));
    }
}
