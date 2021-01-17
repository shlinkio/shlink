<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

final class ShortUrlVisited extends AbstractVisitEvent
{
    private ?string $originalIpAddress;

    public function __construct(string $visitId, ?string $originalIpAddress = null)
    {
        parent::__construct($visitId);
        $this->originalIpAddress = $originalIpAddress;
    }

    public function originalIpAddress(): ?string
    {
        return $this->originalIpAddress;
    }
}
