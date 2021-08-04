<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

final class VisitsStats implements JsonSerializable
{
    public function __construct(private int $visitsCount, private int $orphanVisitsCount)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'visitsCount' => $this->visitsCount,
            'orphanVisitsCount' => $this->orphanVisitsCount,
        ];
    }
}
