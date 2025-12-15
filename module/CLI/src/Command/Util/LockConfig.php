<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

final readonly class LockConfig
{
    public const float DEFAULT_TTL = 600.0; // 10 minutes

    private function __construct(
        public string $lockName,
        public bool $isBlocking,
        public float $ttl = self::DEFAULT_TTL,
    ) {
    }

    public static function blocking(string $lockName): self
    {
        return new self($lockName, isBlocking: true);
    }

    public static function nonBlocking(string $lockName): self
    {
        return new self($lockName, isBlocking: false);
    }
}
