<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

final class LockedCommandConfig
{
    public const DEFAULT_TTL = 600.0; // 10 minutes

    private string $lockName;
    private bool $isBlocking;
    private float $ttl;

    private function __construct(string $lockName, bool $isBlocking, float $ttl = self::DEFAULT_TTL)
    {
        $this->lockName = $lockName;
        $this->isBlocking = $isBlocking;
        $this->ttl = $ttl;
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
