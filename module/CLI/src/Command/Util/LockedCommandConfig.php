<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

final class LockedCommandConfig
{
    public const DEFAULT_TTL = 600.0; // 10 minutes

    private function __construct(
        public readonly string $lockName,
        public readonly bool $isBlocking,
        public readonly float $ttl = self::DEFAULT_TTL,
    ) {
    }

    public static function blocking(string $lockName): self
    {
        return new self($lockName, true);
    }

    public static function nonBlocking(string $lockName): self
    {
        return new self($lockName, false);
    }
}
