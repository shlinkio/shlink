<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;

final class ShortUrlCreated implements JsonSerializable
{
    public function __construct(public readonly string $shortUrlId)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'shortUrlId' => $this->shortUrlId,
        ];
    }
}
