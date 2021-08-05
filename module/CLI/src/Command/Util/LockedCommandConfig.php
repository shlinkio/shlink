<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

final class LockedCommandConfig
{
    public const DEFAULT_TTL = 600.0; // 10 minutes

    private function __construct(
        private string $lockName,
        private bool $isBlocking,
        private float $ttl = self::DEFAULT_TTL,
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

    public function lockName(): string
    {
        return $this->lockName;
    }

    public function isBlocking(): bool
    {
        return $this->isBlocking;
    }

    public function ttl(): float
    {
        return $this->ttl;
    }
}
