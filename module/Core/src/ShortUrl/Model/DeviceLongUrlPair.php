<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\Model\DeviceType;

use function Functional\group;
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
        $toRemove = []; // TODO Use when group is removed
        $toKeep = []; // TODO Use when group is removed
        $typesWithNullUrl = group($map, static fn (?string $longUrl) => $longUrl === null ? 'remove' : 'keep');

        $deviceTypesToRemove = [];
        foreach ($typesWithNullUrl['remove'] ?? [] as $deviceType => $_) {
            $deviceTypesToRemove[] = DeviceType::from($deviceType);
        }

        $pairsToKeep = [];
        /**
         * @var string $deviceType
         * @var string $longUrl
         */
        foreach ($typesWithNullUrl['keep'] ?? [] as $deviceType => $longUrl) {
            $pairsToKeep[$deviceType] = self::fromRawTypeAndLongUrl($deviceType, $longUrl);
        }

        return [$pairsToKeep, $deviceTypesToRemove];
    }
}
