<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

final class UrlVisited extends AbstractVisitEvent
{
    public function __construct(string $visitId, private ?string $originalIpAddress = null)
    {
        parent::__construct($visitId);
    }

    public function originalIpAddress(): ?string
    {
        return $this->originalIpAddress;
    }
}
