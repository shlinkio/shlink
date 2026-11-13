<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;
use Override;
use Shlinkio\Shlink\EventDispatcher\Util\JsonUnserializable;

final readonly class ShortUrlCreated implements JsonSerializable, JsonUnserializable
{
    public function __construct(public string $shortUrlId) {}

    #[Override]
    public function jsonSerialize(): array
    {
        return ['shortUrlId' => $this->shortUrlId];
    }

    #[Override]
    public static function fromPayload(array $payload): self
    {
        return new self($payload['shortUrlId'] ?? '');
    }
}
