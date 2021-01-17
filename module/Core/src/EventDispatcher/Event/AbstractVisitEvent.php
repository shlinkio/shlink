<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Event;

use JsonSerializable;

abstract class AbstractVisitEvent implements JsonSerializable
{
    protected string $visitId;

    public function __construct(string $visitId)
    {
        $this->visitId = $visitId;
    }

    public function visitId(): string
    {
        return $this->visitId;
    }

    public function jsonSerialize(): array
    {
        return ['visitId' => $this->visitId];
    }
}
