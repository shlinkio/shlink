<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use JsonSerializable;

final class ShortUrlVisited implements JsonSerializable
{
    private string $visitId;
    private ?string $originalIpAddress;

    public function __construct(string $visitId, ?string $originalIpAddress = null)
    {
        $this->visitId = $visitId;
        $this->originalIpAddress = $originalIpAddress;
    }

    public function visitId(): string
    {
        return $this->visitId;
    }

    public function originalIpAddress(): ?string
    {
        return $this->originalIpAddress;
    }

    public function jsonSerialize(): array
    {
        return ['visitId' => $this->visitId, 'originalIpAddress' => $this->originalIpAddress];
    }
}
