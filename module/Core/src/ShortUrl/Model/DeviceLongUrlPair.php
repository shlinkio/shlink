<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\Model\DeviceType;

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
     * Returns an array with two values.
     *  * The first one is a list of mapped instances for those entries in the map with non-null value
     *  * The second is a list of DeviceTypes which have been provided with value null
     *
     * @param array<string, string | null> $map
     * @return array{array<string, self>, DeviceType[]}
     */
    public static function fromMapToChangeSet(array $map): array
    {
        $pairsToKeep = [];
        $deviceTypesToRemove = [];

        foreach ($map as $deviceType => $longUrl) {
            if ($longUrl === null) {
                $deviceTypesToRemove[] = DeviceType::from($deviceType);
            } else {
                $pairsToKeep[$deviceType] = self::fromRawTypeAndLongUrl($deviceType, $longUrl);
            }
        }

        return [$pairsToKeep, $deviceTypesToRemove];
    }
}
