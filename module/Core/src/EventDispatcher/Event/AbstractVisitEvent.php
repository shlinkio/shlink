<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;

abstract class AbstractVisitEvent implements JsonSerializable
{
    public function __construct(public readonly string $visitId)
    {
    }

    public function jsonSerialize(): array
    {
        return ['visitId' => $this->visitId];
    }
}
