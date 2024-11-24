<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;
use Shlinkio\Shlink\EventDispatcher\Util\JsonUnserializable;

final readonly class UrlVisited implements JsonSerializable, JsonUnserializable
{
    final public function __construct(
        public string $visitId,
        public string|null $originalIpAddress = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return ['visitId' => $this->visitId, 'originalIpAddress' => $this->originalIpAddress];
    }

    public static function fromPayload(array $payload): self
    {
        return new self($payload['visitId'] ?? '', $payload['originalIpAddress'] ?? null);
    }
}
