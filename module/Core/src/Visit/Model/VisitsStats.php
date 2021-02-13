<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

final class VisitsStats implements JsonSerializable
{
    private int $visitsCount;
    private int $orphanVisitsCount;

    public function __construct(int $visitsCount, int $orphanVisitsCount)
    {
        $this->visitsCount = $visitsCount;
        $this->orphanVisitsCount = $orphanVisitsCount;
    }

    public function jsonSerialize(): array
    {
        return [
            'visitsCount' => $this->visitsCount,
            'orphanVisitsCount' => $this->orphanVisitsCount,
        ];
    }
}
