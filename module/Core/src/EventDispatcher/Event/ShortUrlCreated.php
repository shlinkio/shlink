<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;
use Shlinkio\Shlink\EventDispatcher\Util\JsonUnserializable;

final readonly class ShortUrlCreated implements JsonSerializable, JsonUnserializable
{
    public function __construct(public string $shortUrlId)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'shortUrlId' => $this->shortUrlId,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self($payload['shortUrlId'] ?? '');
    }
}
