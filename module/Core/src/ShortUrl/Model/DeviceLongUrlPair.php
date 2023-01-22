<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\Model\DeviceType;

use function array_values;
use function Functional\group;
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
     * Returns an array with two values.
     *  * The first one is a list of mapped instances for those entries in the map with non-null value
     *  * The second is a list of DeviceTypes which have been provided with value null
     *
     * @param array<string, string> $map
     * @return array{array<string, self>, DeviceType[]}
     */
    public static function fromMapToChangeSet(array $map): array
    {
        $typesWithNullUrl = group($map, static fn (?string $longUrl) => $longUrl === null ? 'remove' : 'keep');
        $deviceTypesToRemove = array_values(map(
            $typesWithNullUrl['remove'] ?? [],
            static fn ($_, string $deviceType) => DeviceType::from($deviceType),
        ));
        $pairsToKeep = map(
            $typesWithNullUrl['keep'] ?? [],
            fn (string $longUrl, string $deviceType) => self::fromRawTypeAndLongUrl($deviceType, $longUrl),
        );

        return [$pairsToKeep, $deviceTypesToRemove];
    }
}
