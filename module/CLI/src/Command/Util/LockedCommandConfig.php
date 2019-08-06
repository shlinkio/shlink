<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

final class LockedCommandConfig
{
    private const DEFAULT_TTL = 90.0; // 1.5 minutes

    /** @var string */
    private $lockName;
    /** @var bool */
    private $isBlocking;
    /** @var float */
    private $ttl;

    public function __construct(string $lockName, bool $isBlocking = false, float $ttl = self::DEFAULT_TTL)
    {
        $this->lockName = $lockName;
        $this->isBlocking = $isBlocking;
        $this->ttl = $ttl;
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
