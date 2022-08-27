<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

final class UrlVisited extends AbstractVisitEvent
{
    private ?string $originalIpAddress = null;

    public static function withOriginalIpAddress(string $visitId, ?string $originalIpAddress): self
    {
        $instance = new self($visitId);
        $instance->originalIpAddress = $originalIpAddress;

        return $instance;
    }

    public function originalIpAddress(): ?string
    {
        return $this->originalIpAddress;
    }
}
