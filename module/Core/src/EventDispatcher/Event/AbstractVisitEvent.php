<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;
use Shlinkio\Shlink\EventDispatcher\Util\JsonUnserializable;

abstract class AbstractVisitEvent implements JsonSerializable, JsonUnserializable
{
    final public function __construct(
        public readonly string $visitId,
        public readonly string|null $originalIpAddress = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return ['visitId' => $this->visitId, 'originalIpAddress' => $this->originalIpAddress];
    }

    public static function fromPayload(array $payload): self
    {
        return new static($payload['visitId'] ?? '', $payload['originalIpAddress'] ?? null);
    }
}
